<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsumerStatus;
use App\Enums\MerchantType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TransactionService
{
    public function deleteByConsumer(int $consumerId): void
    {
        Transaction::query()->where('consumer_id', $consumerId)->delete();
    }

    public function fetchByConsumer(int $consumerId): Collection
    {
        return Transaction::query()
            ->with(['paymentProfile' => fn ($relation) => $relation->withTrashed(), 'externalPaymentProfile', 'scheduleTransaction'])
            ->where('consumer_id', $consumerId)
            ->where(function (Builder $query): void {
                $query->where('transaction_type', TransactionType::PARTIAL_PIF)
                    ->orWhere(function (Builder $query) {
                        $query->whereNot('transaction_type', TransactionType::PARTIAL_PIF)
                            ->where('status', TransactionStatus::SUCCESSFUL);
                    });
            })
            ->where('status', TransactionStatus::SUCCESSFUL)
            ->get();
    }

    public function fetchHistory(int $consumerId, int $perPage): LengthAwarePaginator
    {
        return Transaction::query()
            ->select(
                'id',
                'company_id',
                'subclient_id',
                'consumer_id',
                'transaction_type',
                'transaction_id',
                'payment_mode',
                'created_at',
                'amount',
                'status',
                'gateway_response',
            )
            ->with([
                'company:id,company_name',
                'subclient:id,subclient_name',
                'consumer:id,first_name,last_name',
                'scheduleTransaction',
            ])
            ->where('consumer_id', $consumerId)
            ->withCasts(['payment_mode' => MerchantType::class])
            ->paginate($perPage);
    }

    public function getTotalOfSuccessfulPayments(int $companyId, ?int $subclientId): ?Transaction
    {
        return Transaction::query()
            ->selectRaw('SUM(amount) as successful_payments')
            ->where('company_id', $companyId)
            ->when($subclientId, fn (Builder $query) => $query->where('subclient_id', $subclientId))
            ->where('status', TransactionStatus::SUCCESSFUL->value)
            ->where('created_at', '>=', now()->subDays(30))
            ->first();
    }

    public function fetchSuccessfulTilled(?string $transactionId, ?string $accountId): ?Transaction
    {
        return Transaction::query()
            ->with('consumer')
            ->withWhereHas('scheduleTransaction', function (HasOne|Builder $query): void {
                $query->where('status', TransactionStatus::SUCCESSFUL)
                    ->where('attempt_count', 1)
                    ->whereNotNull('last_attempted_at');
            })
            ->where('status', TransactionStatus::SUCCESSFUL)
            ->where(function (Builder $query) use ($accountId): void {
                $query->whereHas('company', function (Builder $query) use ($accountId): void {
                    $query->where('tilled_merchant_account_id', $accountId)
                        ->whereNotNull('tilled_webhook_secret');
                })
                    ->orWhereHas('subclient', function (Builder $query) use ($accountId): void {
                        $query->where('tilled_merchant_account_id', $accountId)
                            ->whereNotNull('tilled_webhook_secret');
                    });
            })
            ->where('transaction_id', $transactionId)
            ->first();
    }

    /**
     * @return array<string, float>
     */
    public function fetchLastThirtyDaysAmount(User $user): array
    {
        return Transaction::query()
            ->where('company_id', $user->company_id)
            ->when($user->subclient_id, function (Builder $query) use ($user): void {
                $query->where('subclient_id', $user->subclient_id);
            })
            ->where('created_at', '>=', today()->subDays(30)->toDateTimeString())
            ->where('status', TransactionStatus::SUCCESSFUL->value)
            ->selectRaw('SUM(amount) as amount, created_at as created_at')
            ->groupBy('created_at')
            ->get()
            ->mapWithKeys(fn (Transaction $transaction): array => [
                $transaction->created_at->toDateTimeString() => $transaction->amount,
            ])
            ->toArray();
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string,
     *  per_page: int
     * }  $data
     */
    public function fetchRecentlyOfCompany(array $data): LengthAwarePaginator
    {
        return $this->fetchRecentlyBuilder($data)->paginate($data['per_page']);
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
    public function getRecentlyOfCompany(array $data): Collection
    {
        return $this->fetchRecentlyBuilder($data)->get();
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string,
     *  per_page: int,
     *  status: string
     * }  $data
     */
    public function lastThirtyDays(array $data): LengthAwarePaginator
    {
        return Transaction::query()
            ->with([
                'consumer.company:id,company_name',
                'consumer.subclient:id,company_id,subclient_name',
            ])
            ->where('company_id', $data['company_id'])
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->where('status', $data['status'])
            ->where('created_at', '>=', today()->subDays(30)->toDateTimeString())
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereHas('consumer', function (Builder $query) use ($data): void {
                        $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                            ->orSearch('member_account_number', $data['search']);
                    });
                });
            })
            ->when($data['column'] === 'created_at', function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            })
            ->when($data['column'] === 'amount', function (Builder $query) use ($data): void {
                $query->orderByRaw("CAST(amount AS DECIMAL(10, 2)) {$data['direction']}")->orderBy('id');
            })
            ->when($data['column'] === 'consumer_name', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Consumer::query()
                        ->selectRaw("TRIM(CONCAT_WS(' ', first_name, last_name))")
                        ->whereColumn('transactions.consumer_id', 'consumers.id'),
                    $data['direction']
                )
                    ->orderBy('id');
            })
            ->when($data['column'] === 'member_account_number', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Consumer::query()
                        ->select('member_account_number')
                        ->whereColumn('transactions.consumer_id', 'consumers.id'),
                    $data['direction']
                )
                    ->orderBy('id');
            })
            ->paginate($data['per_page']);
    }

    public function successful(null|string|int $transactionId, mixed $transactionResponse, ScheduleTransaction $scheduleTransaction, ConsumerNegotiationService $consumerNegotiationService): Transaction
    {
        $transaction = $this->transaction($transactionId, $scheduleTransaction->consumer, $transactionResponse);

        $isLastInstallment = ScheduleTransaction::query()
            ->whereIn('status', [TransactionStatus::FAILED, TransactionStatus::SCHEDULED])
            ->where('consumer_id', $scheduleTransaction->consumer->id)
            ->count() === 1;

        $scheduleTransaction->consumer->update([
            'has_failed_payment' => false,
            'status' => $isLastInstallment ? ConsumerStatus::SETTLED->value : $scheduleTransaction->consumer->status,
            'current_balance' => max(0, (float) $scheduleTransaction->consumer->current_balance - (float) $scheduleTransaction->amount),
        ]);

        $consumerNegotiationService->updateAfterSuccessFullInstallmentPayment($scheduleTransaction->consumer->consumerNegotiation, (float) $scheduleTransaction->amount);

        $transaction->status = TransactionStatus::SUCCESSFUL;

        return $transaction;
    }

    public function failed(null|int|string $transactionId, Consumer $consumer, mixed $transactionResponse): Transaction
    {
        $transaction = $this->transaction($transactionId, $consumer, $transactionResponse);

        $transaction->status = TransactionStatus::FAILED;

        $consumer->update(['has_failed_payment' => true]);

        return $transaction;
    }

    private function transaction(null|int|string $transactionId, Consumer $consumer, mixed $transactionResponse): Transaction
    {
        $lastTransaction = Transaction::query()->latest()->value('rnn_invoice_id');
        $rnnInvoiceId = $lastTransaction ? $lastTransaction + 1 : 9000;

        return new Transaction([
            'transaction_id' => $transactionId,
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $consumer->id,
            'company_id' => $consumer->company_id,
            'payment_profile_id' => $consumer->paymentProfile->id,
            'gateway_response' => $transactionResponse,
            'payment_mode' => $consumer->paymentProfile->method,
            'subclient_id' => $consumer->subclient_id,
            'rnn_invoice_id' => $rnnInvoiceId,
            'superadmin_process' => 0,
        ]);
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
    private function fetchRecentlyBuilder(array $data): Builder
    {
        return Transaction::query()
            ->withWhereHas('consumer', function ($query) use ($data): void {
                $query->whereIn('status', [
                    ConsumerStatus::PAYMENT_SETUP->value,
                    ConsumerStatus::SETTLED->value,
                    ConsumerStatus::PAYMENT_ACCEPTED->value,
                ])
                    ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                        $query->where('subclient_id', $data['subclient_id']);
                    })
                    ->where('company_id', $data['company_id'])
                    ->when($data['search'], function (Builder $query) use ($data): void {
                        $query->where(function (Builder $query) use ($data): void {
                            $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                                ->orSearch('member_account_number', $data['search']);
                        });
                    });
            })
            ->where('status', TransactionStatus::SUCCESSFUL)
            ->when(in_array($data['column'], ['created_at', 'transaction_type']), function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            })
            ->when($data['column'] === 'amount', function (Builder $query) use ($data): void {
                $query->orderByRaw("CAST(amount AS UNSIGNED) {$data['direction']}")->orderBy('id');
            })
            ->when(
                in_array($data['column'], ['consumer_name', 'member_account_number', 'subclient_name', 'placement_date']),
                function (Builder $query) use ($data): void {
                    $selectedColumn = $data['column'] === 'consumer_name' ? "TRIM(CONCAT_WS(' ', first_name, last_name))" : $data['column'];

                    $query->orderBy(
                        Consumer::query()
                            ->selectRaw($selectedColumn)
                            ->whereColumn('transactions.consumer_id', 'consumers.id'),
                        $data['direction']
                    )
                        ->orderBy('id');
                }
            )
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->where('company_id', $data['company_id']);
    }

    /**
     * @param array{
     *  company_id: ?int,
     *  subclient_id: ?int,
     *  start_date: string,
     *  end_date: string,
     * }  $data
     */
    public function getTransactionReports(array $data): Collection
    {
        return Transaction::query()
            ->select('id', 'consumer_id', 'payment_profile_id', 'transaction_id', 'transaction_type', 'created_at', 'status', 'amount')
            ->with([
                'consumer:id,status,account_number,first_name,last_name,subclient_id',
                'paymentProfile' => fn (BelongsTo $relation) => $relation->select('id', 'profile_id')->whereNull('deleted_at'),
            ])
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->whereRelation('consumer', 'subclient_id', $data['subclient_id']);
            })
            ->whereBetween('created_at', [Carbon::parse($data['start_date'])->startOfDay(), Carbon::parse($data['end_date'])->endOfDay()])
            ->latest()
            ->get();
    }

    /**
     * @param  array{ company_id: ?int, subclient_id: ?int, start_date: string, end_date: string }  $data
     */
    public function getConsumerPaymentsReport(array $data): Collection
    {
        return Transaction::query()
            ->select([
                'consumer_id',
                'transaction_type',
                'created_at',
                'amount',
                'rnn_share',
                'company_share',
            ])
            ->with(['consumer:id,first_name,last_name,dob,last4ssn,account_number,original_account_name,member_account_number,reference_number,statement_id_number,subclient_id,subclient_name,subclient_account_number,placement_date,expiry_date'])
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
            ->where('status', TransactionStatus::SUCCESSFUL)
            ->get();
    }

    /**
     * @param  array{company_id: int, from: string, to: string}  $data
     */
    public function fetchProcessCreditorPayments(array $data): EloquentCollection
    {
        return Transaction::query()
            ->where('company_id', $data['company_id'])
            ->whereHas('company', fn (Builder $query) => $query->whereNull('tilled_merchant_account_id'))
            ->whereNull('rnn_share_pass')
            ->where('status', TransactionStatus::SUCCESSFUL)
            ->where('superadmin_process', false)
            ->whereRaw('DATE(created_at) BETWEEN ? and ?', [$data['from'], $data['to']])
            ->get();
    }
}
