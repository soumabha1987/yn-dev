<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TransactionService
{
    public function fetchByConsumer(int $consumerId): Collection
    {
        return Transaction::query()
            ->with(['paymentProfileWithTrash', 'scheduleTransaction', 'externalPaymentProfile'])
            ->where('consumer_id', $consumerId)
            ->where(function (Builder $query) {
                $query->where('transaction_type', TransactionType::PARTIAL_PIF)
                    ->orWhere(function (Builder $query) {
                        $query->whereNot('transaction_type', TransactionType::PARTIAL_PIF)
                            ->where('status', TransactionStatus::SUCCESSFUL);
                    });
            })
            ->where('status', TransactionStatus::SUCCESSFUL)
            ->get();
    }

    public function fetchScheduleTransactions(Consumer $consumer): Collection
    {
        return ScheduleTransaction::query()
            ->with('paymentProfile')
            ->where('consumer_id', $consumer->id)
            ->whereIn('status', [TransactionStatus::SCHEDULED->value, TransactionStatus::FAILED->value])
            ->orderBy('schedule_date')
            ->get();
    }

    public function fetch(Consumer $consumer): Collection
    {
        return Transaction::query()
            ->with(['paymentProfileWithTrash', 'scheduleTransaction'])
            ->where('consumer_id', $consumer->id)
            ->get();
    }

    public function fetchSuccessTransactions(int $consumerId): Collection
    {
        return Transaction::query()
            ->with(['paymentProfile' => fn ($relation) => $relation->withTrashed(), 'scheduleTransaction', 'externalPaymentProfile'])
            ->where('consumer_id', $consumerId)
            ->where(function (Builder $query): void {
                $query->where('transaction_type', TransactionType::PARTIAL_PIF)
                    ->orWhere(function (Builder $query) {
                        $query->whereNot('transaction_type', TransactionType::PARTIAL_PIF)
                            ->where('status', TransactionStatus::SUCCESSFUL);
                    });
            })
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchTransactionsWithNegotiationAmount(?ConsumerNegotiation $consumerNegotiation, Consumer $consumer): array
    {
        $negotiationCurrentAmount = $this->negotiationCurrentAmount($consumerNegotiation);

        $scheduleTransactions = $this->fetchScheduleTransactions($consumer);

        $transactions = $this->fetchSuccessTransactions($consumer->id);

        return compact('scheduleTransactions', 'transactions', 'negotiationCurrentAmount');
    }

    public function negotiationCurrentAmount(ConsumerNegotiation $consumerNegotiation): null|int|string
    {
        $negotiationCurrentAmount = 0;

        /** @var ?NegotiationType $negotiationType */
        $negotiationType = $consumerNegotiation->negotiation_type;

        if ($negotiationType === NegotiationType::PIF) {
            $negotiationCurrentAmount = $consumerNegotiation->offer_accepted
                ? $consumerNegotiation->one_time_settlement
                : $consumerNegotiation->counter_one_time_amount;
        }

        if ($negotiationType === NegotiationType::INSTALLMENT) {
            $negotiationCurrentAmount = $consumerNegotiation->offer_accepted
            ? $consumerNegotiation->negotiate_amount
            : $consumerNegotiation->counter_negotiate_amount;
        }

        return $negotiationCurrentAmount;
    }
}
