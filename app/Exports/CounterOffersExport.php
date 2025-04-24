<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\NegotiationType;
use App\Enums\Role;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\User;
use App\Services\ConsumerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CounterOffersExport implements FromCollection, WithHeadingRow, WithHeadings
{
    protected ConsumerService $consumerService;

    public function __construct(
        private Collection $counterOffers,
        private User $user
    ) {
        $this->consumerService = app(ConsumerService::class);
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->counterOffers->map(function (Consumer $consumer): array {
            /** @var ConsumerNegotiation $consumerNegotiation */
            $consumerNegotiation = $consumer->consumerNegotiation;

            $data = collect([
                'account_number' => $consumer->account_number,
                'name' => $consumer->first_name . ' ' . $consumer->last_name,
                'email' => $consumer->email1,
                'mobile' => $consumer->mobile1,
                'original_offer_discounted_pif_amount' => Number::currency($this->consumerService->discountedPifAmount($consumer) ?? 0),
                'counter_offer_discounted_pif_amount' => Number::currency((float) ($consumerNegotiation->counter_one_time_amount ?? 0)),
                'original_offer_discounted_settlement_amount' => Number::currency($this->consumerService->discountedPaymentPlanBalance($consumer, $consumerNegotiation) ?? 0),
                'counter_offer_discounted_settlement_amount' => Number::currency((float) ($consumerNegotiation->counter_negotiate_amount ?? 0)),
                'first_pay_date' => $consumerNegotiation->first_pay_date->format('d F Y'),
                'counter_first_pay_date' => $consumerNegotiation->counter_first_pay_date?->format('d F Y'),
                'note' => $consumerNegotiation->note ?? '',
                'counter_note' => $consumerNegotiation->counter_note ?? '',
                'original_offer_minimum_monthly_payment' => '',
                'counter_offer_minimum_monthly_payment' => '',
            ]);

            if ($this->user->hasRole(Role::CREDITOR)) {
                $data->putAfter([
                    'subclient_name' => $consumer->subclient->subclient_name ?? '',
                ], 'name');
            }

            if ($consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
                return $data->merge([
                    'original_offer_minimum_monthly_payment' => Number::currency($this->consumerService->minimumMonthlyPayment($consumer) ?? 0),
                    'counter_offer_minimum_monthly_payment' => Number::currency((float) ($consumerNegotiation->counter_monthly_amount ?? 0)),
                ])->all();
            }

            return $data->all();
        });
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $headings = collect([
            __('Account number'),
            __('Name'),
            __('Email'),
            __('Mobile'),
            __('Original offer discounted pif amount'),
            __('Counter offer discounted pif amount'),
            __('Original offer discounted settlement amount'),
            __('Counter offer discounted settlement amount'),
            __('First pay date'),
            __('Counter first pay date'),
            __('Note'),
            __('Counter note'),
            __('Original offer minimum monthly payment'),
            __('Counter offer minimum monthly payment'),
        ]);

        if ($this->user->hasRole(Role::CREDITOR)) {
            return $headings->putAfter([__('Sub Account Name')], __('Name'), true)->all();
        }

        return $headings->all();
    }
}
