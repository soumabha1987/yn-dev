<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\ConsumerOffers\ViewOffer;

use App\Enums\NegotiationType;
use App\Livewire\Creditor\ConsumerOffers\ViewOffer;
use App\Rules\SingleSpace;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CreditorOfferForm extends Form
{
    public $settlement_discount_amount = '';

    public $payment_plan_discount_amount = '';

    public $monthly_amount = '';

    public $counter_first_pay_date = '';

    public $counter_note = '';

    public function init(array $creditorOffer): void
    {
        $this->fill([
            'counter_first_pay_date' => $creditorOffer['first_payment_date']?->toDateString(),
            'settlement_discount_amount' => $creditorOffer['settlement_discount_offer_amount'] ?? '',
            'payment_plan_discount_amount' => $creditorOffer['payment_plan_offer_amount'] ?? '',
            'monthly_amount' => $creditorOffer['minimum_monthly_payment'] ?? '',
            'counter_note' => $creditorOffer['counter_note'],
        ]);
    }

    public function rules(): array
    {
        /** @var ViewOffer $component */
        $component = $this->component;

        $isInstallmentType = $component->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT;

        return [
            'settlement_discount_amount' => ['nullable', Rule::requiredIf(! $isInstallmentType), 'numeric', 'gt:0'],
            'payment_plan_discount_amount' => ['nullable', Rule::requiredIf($isInstallmentType), 'numeric', 'gt:0'],
            'monthly_amount' => ['nullable', Rule::requiredIf($isInstallmentType), 'numeric', 'gt:0'],
            'counter_first_pay_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'counter_note' => ['nullable', 'string', 'max:100', new SingleSpace],
        ];
    }
}
