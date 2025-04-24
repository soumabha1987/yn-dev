<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsumerStatus;
use App\Enums\TransactionStatus;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ScheduleTransactionService
{
    public function markScheduledAndFailedAsCancelled(int $consumerId): void
    {
        ScheduleTransaction::query()
            ->where('consumer_id', $consumerId)
            ->whereIn('status', [TransactionStatus::FAILED->value, TransactionStatus::SCHEDULED->value])
            ->update(['status' => TransactionStatus::CANCELLED]);
    }

    public function deleteByConsumer(int $consumerId): void
    {
        ScheduleTransaction::query()->where('consumer_id', $consumerId)->delete();
    }

    public function fetchByConsumer(int $consumerId): Collection
    {
        return ScheduleTransaction::query()
            ->select('id', 'payment_profile_id', 'schedule_date', 'amount', 'status')
            ->with('paymentProfile', fn ($relation) => $relation->withTrashed())
            ->where('consumer_id', $consumerId)
            ->whereIn('status', [TransactionStatus::SCHEDULED->value, TransactionStatus::FAILED->value, TransactionStatus::CANCELLED->value])
            ->orderBy('schedule_date')
            ->get();
    }

    public function calculateScheduledAndFailedTransactionAmount(Company $company): string|int
    {
        return ScheduleTransaction::query()
            ->where('company_id', $company->id)
            ->whereIn('status', [TransactionStatus::FAILED->value, TransactionStatus::SCHEDULED->value])
            ->sum('amount');
    }

    public function findByTransaction(int $transactionId): ?ScheduleTransaction
    {
        return ScheduleTransaction::query()
            ->where('transaction_id', $transactionId)
            ->where('status', TransactionStatus::FAILED->value)
            ->first();
    }

    public function deleteScheduledOfConsumer(int $consumerId): void
    {
        ScheduleTransaction::query()
            ->where('consumer_id', $consumerId)
            ->where(function (Builder $query) {
                $query->where('status', TransactionStatus::SCHEDULED->value)
                    ->orWhere('schedule_date', '<=', now());
            })
            ->delete();
    }

    public function fetchScheduledOfConsumer(int $consumerId, int $perPage): LengthAwarePaginator
    {
        return ScheduleTransaction::query()
            ->select('id', 'company_id', 'subclient_id', 'payment_profile_id', 'schedule_date', 'previous_schedule_date', 'amount', 'status')
            ->with('company:id,company_name,timezone')
            ->with('paymentProfile', fn ($relation) => $relation->withTrashed())
            ->where('consumer_id', $consumerId)
            ->whereIn('status', [TransactionStatus::SCHEDULED, TransactionStatus::FAILED])
            ->oldest('schedule_date')
            ->paginate($perPage);
    }

    public function fetchCancelledOfConsumer(int $consumerId, int $perPage): LengthAwarePaginator
    {
        return ScheduleTransaction::query()
            ->select('id', 'company_id', 'subclient_id', 'payment_profile_id', 'schedule_date', 'amount', 'status')
            ->with([
                'company:id,company_name',
                'subclient:id,subclient_name',
            ])
            ->withWhereHas('paymentProfile', fn ($relation) => $relation->whereNull('deleted_at'))
            ->where('consumer_id', $consumerId)
            ->where('status', TransactionStatus::CANCELLED)
            ->paginate($perPage);
    }

    public function getScheduledPaymentOfLastThirtyDays(int $companyId, ?int $subclientId)
    {
        return ScheduleTransaction::query()
            ->selectRaw('SUM(amount) as scheduled_payments')
            ->withWhereHas('consumer', function (Builder|BelongsTo $query) use ($companyId, $subclientId): void {
                $query->with([
                    'company:id,company_name',
                    'subclient:id,company_id,subclient_name',
                ])
                    ->where('company_id', $companyId)
                    ->when($subclientId, function (Builder $query) use ($subclientId): void {
                        $query->where('subclient_id', $subclientId);
                    });
            })
            ->where('company_id', $companyId)
            ->when($subclientId, function (Builder $query) use ($subclientId): void {
                $query->where('subclient_id', $subclientId);
            })
            ->where('status', TransactionStatus::SCHEDULED->value)
            ->whereNotNull('payment_profile_id')
            ->whereRaw('DATE(schedule_date) BETWEEN ? and ?', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->first();
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string,
     * }  $data
     */
    public function getUpcomingTransactions(array $data): Collection
    {
        return $this->upcomingTransactionBuilder($data)->get();
    }

    /**
     * @param  array{
     *  per_page: int,
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string
     * }  $data
     */
    public function fetchUpcomingTransactions(array $data): LengthAwarePaginator
    {
        return $this->upcomingTransactionBuilder($data)->paginate($data['per_page']);
    }

    /**
     * @param  array{
     *  per_page: int,
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string
     * }  $data
     */
    public function fetchPaymentForecast(array $data): LengthAwarePaginator
    {
        return ScheduleTransaction::query()
            ->with([
                'company:id,company_name',
                'subclient:id,company_id,subclient_name',
            ])
            ->where('status', TransactionStatus::SCHEDULED->value)
            ->withWhereHas('consumer', function (Builder|BelongsTo $query) use ($data): void {
                $query->where('company_id', $data['company_id'])
                    ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                        $query->where('subclient_id', $data['subclient_id']);
                    })
                    ->whereNot('status', ConsumerStatus::DEACTIVATED->value);
            })
            ->where('company_id', $data['company_id'])
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->whereNotNull('payment_profile_id')
            ->whereRaw('DATE(schedule_date) BETWEEN ? and ?', [today()->toDateString(), today()->addDays(30)->toDateString()])
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereHas('consumer', function (Builder $query) use ($data): void {
                        $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                            ->orSearch('member_account_number', $data['search']);
                    });
                });
            })
            ->when(in_array($data['column'], ['amount', 'schedule_date']), function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            })
            ->when($data['column'] === 'consumer_name', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Consumer::query()
                        ->selectRaw("TRIM(CONCAT_WS(' ', first_name, last_name))")
                        ->whereColumn('schedule_transactions.consumer_id', 'consumers.id'),
                    $data['direction']
                )
                    ->orderBy('id');
            })
            ->when($data['column'] === 'member_account_number', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Consumer::query()
                        ->select('member_account_number')
                        ->whereColumn('schedule_transactions.consumer_id', 'consumers.id'),
                    $data['direction']
                )
                    ->orderBy('id');
            })
            ->paginate($data['per_page']);
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string
     * }  $data
     */
    private function upcomingTransactionBuilder(array $data): Builder
    {
        return ScheduleTransaction::query()
            ->withWhereHas('consumer', function (Builder|BelongsTo $query) use ($data): void {
                $query->with(['company:id,company_name', 'subclient:id,company_id,subclient_name'])
                    ->where('company_id', $data['company_id'])
                    ->when($data['subclient_id'], function (Builder $builder) use ($data): void {
                        $builder->where('subclient_id', $data['subclient_id']);
                    });
            })
            ->withWhereHas('paymentProfile', function (Builder|BelongsTo $query): void {
                $query->whereNull('deleted_at');
            })
            ->where('status', TransactionStatus::SCHEDULED->value)
            ->where('company_id', $data['company_id'])
            ->when($data['subclient_id'], function (Builder $builder) use ($data): void {
                $builder->where('subclient_id', $data['subclient_id']);
            })
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereHas('consumer', function (Builder $query) use ($data): void {
                        $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                            ->orSearch('member_account_number', $data['search']);
                    });

                    try {
                        $date = Carbon::parse($data['search'])->toDateString();
                        $query->orSearch('schedule_date', $date);
                    } catch (Exception $exception) {
                        Log::channel('daily')->error('User passed date format not supported', [
                            'search' => $data['search'],
                            'message' => $exception->getMessage(),
                        ]);
                    }
                });
            })
            ->when(in_array($data['column'], ['schedule_date', 'transaction_type']), function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            })
            ->when($data['column'] === 'amount', function (Builder $query) use ($data): void {
                $query->orderByRaw("CAST(amount AS DECIMAL(10, 2)) {$data['direction']}")->orderBy('id');
            })
            ->when(
                in_array($data['column'], ['consumer_name', 'member_account_number', 'original_account_name', 'placement_date', 'subclient_name']),
                function (Builder $query) use ($data): void {
                    $selectedColumn = $data['column'] === 'consumer_name' ? "TRIM(CONCAT_WS(' ', first_name, last_name))" : $data['column'];

                    $query->orderBy(
                        Consumer::query()
                            ->selectRaw($selectedColumn)
                            ->whereColumn('schedule_transactions.consumer_id', 'consumers.id'),
                        $data['direction']
                    )
                        ->orderBy('id');
                }
            );
    }

    /**
     * @param  array{
     *      company_id: ?int,
     *      subclient_id: ?int,
     *      start_date: string,
     *      end_date: string,
     *}  $data
     */
    public function getGenerateReports(array $data): Collection
    {
        return ScheduleTransaction::query()
            ->select('id', 'consumer_id', 'payment_profile_id', 'amount', 'status', 'schedule_date')
            ->with([
                'consumer:id,account_number,first_name,last_name,subclient_id',
                'paymentProfile' => fn (BelongsTo $relation) => $relation
                    ->select('id', 'method')
                    ->whereNull('deleted_at'),
            ])
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->whereRelation('consumer', 'subclient_id', $data['subclient_id']);
            })
            ->where('status', TransactionStatus::SCHEDULED->value)
            ->whereBetween('schedule_date', [
                Carbon::parse($data['start_date'])->toDateString(),
                Carbon::parse($data['end_date'])->toDateString(),
            ])
            ->latest()
            ->get();
    }

    /**
     * @return array{
     *  yn_share: numeric|string,
     *  company_share: numeric|string,
     *  share_percentage: float,
     * }
     */
    public function calculateShareAmount(Consumer $consumer, float $amount): array
    {
        $revenueSharePercentage = ScheduleTransaction::query()
            ->where('consumer_id', $consumer->id)
            ->whereIn('status', [TransactionStatus::SCHEDULED, TransactionStatus::FAILED])
            ->first()
            ->revenue_share_percentage;

        $ynShare = number_format(($amount * $revenueSharePercentage / 100), 2, thousands_separator: '');
        $companyShare = number_format(($amount - $ynShare), 2, thousands_separator: '');

        return ['yn_share' => $ynShare, 'company_share' => $companyShare, 'share_percentage' => $revenueSharePercentage];
    }

    /**
     * @param  array{
     *  company_id: int,
     *  search: string,
     *  column: string,
     *  direction: string,
     * }  $data
     */
    public function exportFailedScheduleTransaction(array $data): Collection
    {
        return $this->failedScheduleTransactionBuilder($data)->get();
    }

    /**
     * @param array{
     *  company_id: int,
     *  search: string,
     *  column: string,
     *  direction: string,
     *  per_page: int,
     * } $data
     */
    public function fetchFailedScheduleTransaction(array $data): LengthAwarePaginator
    {
        return $this->failedScheduleTransactionBuilder($data)->paginate($data['per_page']);
    }

    /**
     * @param  array{
     *  company_id: int,
     *  search: string,
     *  column: string,
     *  direction: string
     * }  $data
     */
    private function failedScheduleTransactionBuilder(array $data): Builder
    {
        return ScheduleTransaction::query()
            ->with([
                'consumer:id,member_account_number,first_name,last_name,original_account_name,subclient_name,placement_date',
            ])
            ->where('company_id', $data['company_id'])
            ->where('status', TransactionStatus::FAILED)
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereHas('consumer', function (Builder $query) use ($data): void {
                        $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                            ->orSearch('member_account_number', $data['search']);
                    });
                });
            })
            ->when(
                in_array($data['column'], ['consumer_name', 'member_account_number', 'original_account_name', 'subclient_name', 'placement_date']),
                function (Builder $query) use ($data): void {
                    $selectedColumn = $data['column'] === 'consumer_name' ? "TRIM(CONCAT_WS(' ', first_name, last_name))" : $data['column'];

                    $query->orderBy(
                        Consumer::query()
                            ->selectRaw($selectedColumn)
                            ->whereColumn('schedule_transactions.consumer_id', 'consumers.id'),
                        $data['direction']
                    )
                        ->orderBy('id');
                }
            )
            ->when(in_array($data['column'], ['last_attempted_at', 'schedule_date']), function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            });
    }

    public function getTotalOfFailedPayments(int $companyId, ?int $subclientId): ?ScheduleTransaction
    {
        return ScheduleTransaction::query()
            ->selectRaw('SUM(amount) as failed_payments')
            ->where('company_id', $companyId)
            ->when($subclientId, fn (Builder $query) => $query->where('subclient_id', $subclientId))
            ->where('status', TransactionStatus::FAILED->value)
            ->where('last_attempted_at', '>=', now()->subDays(30))
            ->first();
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string,
     *  per_page: int,
     * }  $data
     */
    public function lastThirtyDays(array $data): LengthAwarePaginator
    {
        return ScheduleTransaction::query()
            ->with([
                'transaction:transaction_id',
                'consumer.subclient:id,company_id,subclient_name',
            ])
            ->where('company_id', $data['company_id'])
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->where('status', TransactionStatus::FAILED->value)
            ->where('last_attempted_at', '>=', today()->subDays(30)->toDateTimeString())
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereHas('consumer', function (Builder $query) use ($data): void {
                        $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                            ->orSearch('member_account_number', $data['search']);
                    });
                });
            })
            ->when($data['column'] === 'last_attempted_at', function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            })
            ->when($data['column'] === 'amount', function (Builder $query) use ($data): void {
                $query->orderByRaw("CAST(amount AS DECIMAL(10, 2)) {$data['direction']}")->orderBy('id');
            })
            ->when($data['column'] === 'consumer_name', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Consumer::query()
                        ->selectRaw("TRIM(CONCAT_WS(' ', first_name, last_name))")
                        ->whereColumn('schedule_transactions.consumer_id', 'consumers.id'),
                    $data['direction']
                )
                    ->orderBy('id');
            })
            ->when($data['column'] === 'member_account_number', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Consumer::query()
                        ->select('member_account_number')
                        ->whereColumn('schedule_transactions.consumer_id', 'consumers.id'),
                    $data['direction']
                )
                    ->orderBy('id');
            })
            ->paginate($data['per_page']);
    }
}
