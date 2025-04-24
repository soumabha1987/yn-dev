<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Traits;

use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Livewire\Consumer\Forms\ConsumerOfferForm;
use App\Livewire\Consumer\Forms\CustomOfferForm;
use App\Models\Consumer;
use App\Services\Consumer\DiscountService;
use Carbon\Carbon;
use Illuminate\Support\Number;
use Illuminate\Validation\Validator;

/**
 * @property-read DiscountService $discountService
 * @property-read Consumer $consumer
 * @property-read CustomOfferForm|ConsumerOfferForm $form
 */
trait ValidateCounterOffer
{
    public function bootValidateCounterOffer(): void
    {
        $this->consumer->loadMissing(['company', 'subclient', 'consumerNegotiation']);

        $this->form->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                if ($validator->errors()->isEmpty()) {
                    match ($validator->getData()['negotiation_type'] ?? $this->consumer->consumerNegotiation->negotiation_type->value) {
                        NegotiationType::PIF->value => $this->validateSettlementAmount($validator),
                        NegotiationType::INSTALLMENT->value => $this->validateMonthlyAmount($validator),
                    };
                }
            });
        });
    }

    private function validateSettlementAmount(Validator $validator): void
    {
        $data = $validator->getData();

        [$minSettlementPercentage, $maxFirstPayDays] = $this->discountService->getPifMinimumPercentageAndMaxDate($this->consumer);

        $minimumSettlementAmount = (float) (($this->consumer->current_balance * $minSettlementPercentage) / 100);

        $validator->errors()->addIf(
            ($data['amount'] ?? $data['monthly_amount']) < $minimumSettlementAmount,
            ($data['amount'] ?? false) ? 'amount' : 'monthly_amount',
            __('Your Minimum Settlement Offer must be at least :amount (:percentage % of the discounted balance).', ['amount' => Number::currency($minimumSettlementAmount), 'percentage' => $minSettlementPercentage]),
        );

        $firstPayDate = Carbon::parse($data['first_pay_date'] ?? $data['counter_first_pay_date']);

        $validator->errors()->addIf(
            today()->addDays($maxFirstPayDays)->lt($firstPayDate),
            ($data['first_pay_date'] ?? false) ? 'first_pay_date' : 'counter_first_pay_date',
            __('This member only allows first payment dates on or before :first_pay_date.', ['first_pay_date' => today()->addDays($maxFirstPayDays)->format('M d, Y')]),
        );
    }

    private function validateMonthlyAmount(Validator $validator): void
    {
        $data = $validator->getData();

        $monthlyAmount = $this->calculateMonthlyAmount((float) ($data['amount'] ?? $data['monthly_amount']), $data['installment_type'] ?? $this->consumer->consumerNegotiation->installment_type->value);

        [$minPayPlanPercentage, $maxFirstPayDays] = $this->discountService->getPpaMinimumPercentageAndMaxDate($this->consumer);

        $minimumMonthlyToPay = (float) (($this->minimumPpaDiscountedAmount * $minPayPlanPercentage) / 100);

        $validator->errors()->addIf(
            $minimumMonthlyToPay > $monthlyAmount,
            ($data['amount'] ?? false) ? 'amount' : 'monthly_amount',
            __('The monthly payment amount is below the minimum required amount. To proceed, ensure that the monthly payment is at least :amount.', ['amount' => Number::currency($minimumMonthlyToPay)])
        );

        $firstPayDate = Carbon::parse($data['first_pay_date'] ?? $data['counter_first_pay_date']);

        $validator->errors()->addIf(
            today()->addDays($maxFirstPayDays)->lt($firstPayDate),
            ($data['first_pay_date'] ?? false) ? 'first_pay_date' : 'counter_first_pay_date',
            __('This member only allows first payment dates on or before :first_pay_date.', ['first_pay_date' => today()->addDays($maxFirstPayDays)->format('M, d Y')]),
        );
    }

    private function calculateMonthlyAmount(float $amount, string $installmentType): float
    {
        return $amount * match ($installmentType) {
            InstallmentType::BIMONTHLY->value => InstallmentType::BIMONTHLY->getAmountMultiplication(),
            InstallmentType::WEEKLY->value => InstallmentType::WEEKLY->getAmountMultiplication(),
            default => InstallmentType::MONTHLY->getAmountMultiplication()
        };
    }
}
