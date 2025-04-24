<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\MerchantSettings;

use App\Enums\MerchantType;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Form;

class USAEpayForm extends Form
{
    public array $merchant_type = [];

    public string $usaepay_key = '';

    public string $usaepay_pin = '';

    public function setData(Collection $merchants): void
    {
        $this->fill([
            'merchant_type' => $merchants->pluck('merchant_type')->map(fn (MerchantType $merchantType) => $merchantType->value)->toArray(),
            'usaepay_key' => $merchants->first()->usaepay_key,
            'usaepay_pin' => $merchants->first()->usaepay_pin,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'merchant_type' => ['required', 'array', Rule::in(MerchantType::values())],
            'usaepay_key' => ['required', 'string'],
            'usaepay_pin' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'merchant_type' => __('Please select at least one payment method.'),
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'usaepay_key' => 'USAePAY Key',
            'usaepay_pin' => 'USAePAY Pin',
        ];
    }
}
