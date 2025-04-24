<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NegotiationType;
use App\Models\ConsumerNegotiation;

class ConsumerNegotiationService
{
    public function deleteByConsumer(int $consumerId): void
    {
        ConsumerNegotiation::query()->where('consumer_id', $consumerId)->delete();
    }

    public function findByConsumer(int $consumerId): ?ConsumerNegotiation
    {
        return ConsumerNegotiation::query()
            ->select(
                'id',
                'negotiation_type',
                'offer_accepted',
                'one_time_settlement',
                'counter_one_time_amount',
                'negotiate_amount',
                'counter_negotiate_amount',
            )
            ->where('consumer_id', $consumerId)
            ->first();
    }

    public function fetchActiveNegotiationOfConsumer(int $consumerId): ?ConsumerNegotiation
    {
        return ConsumerNegotiation::query()
            ->select(
                'id',
                'approved_by',
                'negotiation_type',
                'offer_accepted',
                'one_time_settlement',
                'counter_one_time_amount',
                'counter_offer_accepted',
                'payment_plan_current_balance',
                'negotiate_amount',
                'first_pay_date',
                'monthly_amount',
                'counter_monthly_amount',
                'counter_first_pay_date',
                'counter_negotiate_amount',
                'updated_at'
            )
            ->where('consumer_id', $consumerId)
            ->where('active_negotiation', true)
            ->first();
    }

    public function updateAfterSuccessFullInstallmentPayment(ConsumerNegotiation $consumerNegotiation, float $scheduleTransactionAmount): void
    {
        $currentBalance = 0;

        $currentBalance = match (true) {
            $consumerNegotiation->payment_plan_current_balance !== null => max(0, (float) $consumerNegotiation->payment_plan_current_balance - $scheduleTransactionAmount),
            $consumerNegotiation->negotiation_type === NegotiationType::PIF && $consumerNegotiation->offer_accepted => max(0, (float) $consumerNegotiation->one_time_settlement - $scheduleTransactionAmount),
            $consumerNegotiation->negotiation_type === NegotiationType::PIF && $consumerNegotiation->counter_offer_accepted => max(0, (float) $consumerNegotiation->counter_one_time_amount - $scheduleTransactionAmount),
            $consumerNegotiation->offer_accepted => max(0, (float) $consumerNegotiation->negotiate_amount - $scheduleTransactionAmount),
            $consumerNegotiation->counter_offer_accepted => max(0, (float) $consumerNegotiation->counter_negotiate_amount - $scheduleTransactionAmount),
            default => $currentBalance,
        };

        $consumerNegotiation->payment_plan_current_balance = (float) $currentBalance;
        $consumerNegotiation->save();
    }
}
