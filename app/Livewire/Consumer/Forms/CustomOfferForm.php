<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Forms;

use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Models\Consumer;
use App\Services\Consumer\DiscountService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\RequiredIf;
use Livewire\Form;

class CustomOfferForm extends Form
{
    public string $negotiation_type = '';

    public string $installment_type = '';

    public float|string $amount = '';

    public string $first_pay_date = '';

    public string $reason = '';

    public string $note = '';

    public bool $offerSent = false;

    public bool $isOfferAccepted = false;

    public function init(?string $type, Consumer $consumer): void
    {
        $discountService = app(DiscountService::class);

        if ($type === 'settlement') {
            $this->negotiation_type = NegotiationType::PIF->value;
            $this->amount = $discountService->fetchAmountToPayWhenPif($consumer)['discount'];
        }

        if ($type === 'installment') {
            $this->negotiation_type = NegotiationType::INSTALLMENT->value;
            $this->installment_type = InstallmentType::MONTHLY->value;
            $ppaDiscountAmount = $discountService->fetchAmountToPayWhenPpa($consumer);
            $this->amount = (float) number_format($discountService->fetchMonthlyAmount($consumer, $ppaDiscountAmount), 2, thousands_separator: '');
        }
    }

    /**
     * @return array<string, array<int, string | In | RequiredIf>>
     */
    public function rules(): array
    {
        return [
            'negotiation_type' => ['required', Rule::in(NegotiationType::values())],
            'installment_type' => [
                'sometimes',
                Rule::requiredIf(fn (): bool => $this->negotiation_type === NegotiationType::INSTALLMENT->value),
                Rule::in(InstallmentType::values()),
            ],
            'amount' => ['required', 'gt:0'],
            'first_pay_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reason' => ['sometimes', 'string'],
            'note' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
