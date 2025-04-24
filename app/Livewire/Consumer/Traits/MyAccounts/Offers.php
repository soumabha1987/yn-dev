<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Traits\MyAccounts;

use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Models\Consumer;
use Illuminate\Support\Number;

trait Offers
{
    private function offerDetails(Consumer $consumer): array
    {
        if (! $consumer->consumerNegotiation) {
            return [];
        }

        $consumerNegotiation = $consumer->consumerNegotiation;

        $installmentType = $consumerNegotiation->installment_type;

        $negotiationType = $consumerNegotiation->negotiation_type;
        $counterOfferAmount = Number::currency(0.00);

        if ($counterofferMonthlyAmount = $consumerNegotiation->counter_monthly_amount) {
            $counterOfferAmount = Number::currency((float) $counterofferMonthlyAmount);

            if ($consumerNegotiation->installment_type === InstallmentType::WEEKLY) {
                $counterOfferAmount = Number::currency((float) ($counterofferMonthlyAmount * 4));
            }

            if ($installmentType === InstallmentType::BIMONTHLY) {
                $counterOfferAmount = Number::currency((float) ($counterofferMonthlyAmount * 2));
            }
        }

        return [
            'account_profile_details' => $this->getAccountProfileDetails($consumer),
            'offer_summary' => [
                'creditor_offer' => [
                    'one_time_settlement' => $consumer->consumerNegotiation->counter_one_time_amount > 0 ? Number::currency((float) ($consumer->consumerNegotiation->counter_one_time_amount)) : 'N/A',
                    'payment_setup_balance' => $consumer->consumerNegotiation->counter_negotiate_amount > 0 ? Number::currency((float) ($consumer->consumerNegotiation->counter_negotiate_amount)) : 'N/A',
                    'plan_type' => $installmentType?->displayName() ?? __('Pay in full'),
                    'counter_offer_amount' => $negotiationType === NegotiationType::PIF
                        ? Number::currency((float) ($consumer->consumerNegotiation->counter_one_time_amount ?? 0))
                        : $counterOfferAmount,
                    'first_payment_date' => $consumerNegotiation->counter_first_pay_date?->format('M d, Y') ?? 'N/A',
                    'counter_note' => $consumerNegotiation->counter_note ?? 'N/A',
                    'note' => $consumerNegotiation->note ?? 'N/A',
                ],
                'my_last_offer' => $this->getLastOffer($consumer),
            ],
        ];
    }

    private function getLastOffer(Consumer $consumer): array
    {
        if (! $consumer->consumerNegotiation) {
            return [];
        }

        $installmentType = $consumer->consumerNegotiation->installment_type;

        $myLastOffer = 'N/A';

        if ($myLastOfferMonthlyAmount = $consumer->consumerNegotiation->monthly_amount) {
            $myLastOffer = Number::currency((float) $myLastOfferMonthlyAmount);

            if ($installmentType === InstallmentType::WEEKLY) {
                $myLastOffer = Number::currency((float) ($myLastOfferMonthlyAmount * 4));
            }

            if ($installmentType === InstallmentType::BIMONTHLY) {
                $myLastOffer = Number::currency((float) ($myLastOfferMonthlyAmount * 2));
            }
        }

        return [
            'one_time_settlement' => $consumer->consumerNegotiation->one_time_settlement > 0 ? Number::currency((float) ($consumer->consumerNegotiation->one_time_settlement)) : 'N/A',
            'payment_setup_balance' => $consumer->consumerNegotiation->negotiate_amount > 0 ? Number::currency((float) ($consumer->consumerNegotiation->negotiate_amount ?? 0)) : 'N/A',
            'plan_type' => $installmentType?->displayName() ?: __('Pay in full'),
            'my_offer' => $myLastOffer,
            'first_payment_date' => $consumer->consumerNegotiation->first_pay_date?->format('M d, Y') ?? 'N/A',
        ];
    }

    /**
     * @return array{ account_number: string, creditor_name: string, current_balance: mixed }
     */
    private function getAccountProfileDetails(Consumer $consumer): array
    {
        return [
            'account_number' => $consumer->account_number,
            'creditor_name' => $consumer->subclient->subclient_name ?? $consumer->company->company_name,
            'current_balance' => $this->negotiationCurrentAmount($consumer),
        ];
    }
}
