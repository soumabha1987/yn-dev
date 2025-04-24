<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Traits\MyAccounts;

use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Models\Consumer;

trait Conditions
{
    private function accountConditions(Consumer $consumer): ?string
    {
        return [
            ConsumerStatus::JOINED->value => fn (): string => 'joined',
            ConsumerStatus::PAYMENT_ACCEPTED->value => function () use ($consumer): string {
                if ($consumer->payment_setup) {
                    return 'payment_accepted_and_plan_in_scheduled';
                }

                if ($consumer->offer_accepted && $consumer->consumerNegotiation?->negotiation_type === NegotiationType::PIF) {
                    return 'approved_settlement_but_payment_setup_is_pending';
                }

                return 'approved_but_payment_setup_is_pending';
            },
            ConsumerStatus::PAYMENT_SETUP->value => fn (): string => $consumer->counter_offer ? 'creditor_send_an_offer' : 'pending_creditor_response',
            ConsumerStatus::RENEGOTIATE->value => fn (): string => 'renegotiate',
            ConsumerStatus::PAYMENT_DECLINED->value => fn (): string => 'declined',
            ConsumerStatus::DEACTIVATED->value => fn (): string => 'deactivated',
            ConsumerStatus::DISPUTE->value => fn (): string => 'disputed',
            ConsumerStatus::NOT_PAYING->value => fn (): string => 'not_paying',
            ConsumerStatus::SETTLED->value => fn (): string => 'settled',
            ConsumerStatus::HOLD->value => fn (): string => 'hold',
        ][$consumer->status->value]();
    }
}
