<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\CustomContentType;
use App\Enums\State;
use App\Enums\TransactionStatus;
use App\Models\Consumer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class ConsumerService
{
    /**
     * @throws ModelNotFoundException<Consumer>
     */
    public function fetchById(int $consumerId): Consumer
    {
        return Consumer::query()
            ->where(function (Builder $query) {
                $query
                    ->where(function (Builder $query) {
                        $query->where('status', ConsumerStatus::PAYMENT_ACCEPTED)
                            ->where('payment_setup', true)
                            ->whereHas('scheduledTransactions', function (Builder $query): void {
                                $query->whereIn('status', [TransactionStatus::SCHEDULED]);
                            });
                    })
                    ->orWhere(function (Builder $query) {
                        $query->whereNotIn('status', [
                            ConsumerStatus::DEACTIVATED,
                            ConsumerStatus::NOT_PAYING,
                            ConsumerStatus::DISPUTE,
                            ConsumerStatus::SETTLED,
                        ]);
                    });
            })
            ->findOrFail($consumerId);
    }

    /**
     * @param array{
     *   last_name: ?string,
     *   dob: ?string,
     *   last_four_ssn: ?string,
     *   search: ?string,
     *   status: string,
     *  } $data
     */
    public function fetchAccounts(array $data): Collection
    {
        return Consumer::query()
            ->with([
                'company' => function (BelongsTo $relation): void {
                    $relation->with('customContents', function (HasMany $relation): void {
                        $relation->select(['id', 'company_id', 'type', 'content'])
                            ->where('type', CustomContentType::ABOUT_US);
                    });
                },
                'subclient',
                'consumerNegotiation',
                'reason:id,label',
            ])
            ->select([
                '*',
                'state_priority' => DB::raw("
                    CASE
                        WHEN status = '" . ConsumerStatus::PAYMENT_ACCEPTED->value . "' AND payment_setup = 0 THEN 1
                        WHEN status = '" . ConsumerStatus::JOINED->value . "' THEN 2
                        WHEN status = '" . ConsumerStatus::PAYMENT_SETUP->value . "' THEN 3
                        WHEN status = 'notice_sent' THEN 4
                        WHEN status = '" . ConsumerStatus::PAYMENT_ACCEPTED->value . "' AND payment_setup = 1 THEN 5
                        WHEN status = '" . ConsumerStatus::PAYMENT_DECLINED->value . "' THEN 6
                        WHEN status = '" . ConsumerStatus::SETTLED->value . "' THEN 7
                        WHEN status = '" . ConsumerStatus::HOLD->value . "' THEN 8
                        WHEN status = '" . ConsumerStatus::DISPUTE->value . "' THEN 9
                        WHEN status = '" . ConsumerStatus::NOT_PAYING->value . "' THEN 10
                        WHEN status = '" . ConsumerStatus::DEACTIVATED->value . "' THEN 11
                        ELSE 999
                    END as state_priority
                "),
            ])
            ->withCount('transactions')
            ->where('dob', $data['dob'])
            ->where('last_name', $data['last_name'])
            ->where('last4ssn', $data['last_four_ssn'])
            ->when($data['status'] !== 'all', function (Builder $query) use ($data): void {
                $query->where(ConsumerStatus::consumerStates()[$data['status']]['filters']);
            })
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->search('account_number', $data['search'])
                        ->orSearch('member_account_number', $data['search'])
                        ->orSearch('original_account_name', $data['search'])
                        ->orWhereHas('company', function (Builder $query) use ($data): void {
                            $query->search('owner_full_name', $data['search']);
                            $query->orSearch('company_name', $data['search']);
                        })
                        ->orWhereHas('subclient', function (Builder $query) use ($data): void {
                            $query->search('subclient_name', $data['search']);
                        });
                });
            })
            ->orderBy('state_priority')
            ->get();

    }

    public function fetchStates(Consumer $consumer, ?int $companyId = null): SupportCollection
    {
        return Consumer::query()
            ->select('status', 'payment_setup')
            ->where('dob', $consumer->dob)
            ->where('last4ssn', $consumer->last4ssn)
            ->where('last_name', $consumer->last_name)
            ->when($companyId, function (Builder $query) use ($companyId): void {
                $query->where('company_id', $companyId);
            })
            ->groupBy('status')
            ->groupBy('payment_setup')
            ->get()
            ->map(fn (Consumer $consumer) => ConsumerStatus::findStateOfAccount($consumer));
    }

    public function generateAddress(Consumer $consumer): string
    {
        $addressParts = [];

        if ($consumer->address1) {
            $addressParts[] = $consumer->address1;
        }

        if ($consumer->city) {
            $addressParts[] = $consumer->city;
        }

        if ($consumer->state) {
            $stateName = optional(State::tryFrom($consumer->state))->name;
            if ($stateName) {
                $addressParts[] = $stateName;
            }
        }

        if ($consumer->zip) {
            $addressParts[] = $consumer->zip;
        }

        return implode(', ', $addressParts);
    }

    public function markAsJoined(Consumer $consumer)
    {
        return Consumer::query()
            ->where('dob', $consumer->dob)
            ->where('last4ssn', $consumer->last4ssn)
            ->where('last_name', $consumer->last_name)
            ->whereIn('status', [ConsumerStatus::UPLOADED, ConsumerStatus::VISITED, ConsumerStatus::NOT_VERIFIED])
            ->update([
                'status' => ConsumerStatus::JOINED,
            ]);
    }
}
