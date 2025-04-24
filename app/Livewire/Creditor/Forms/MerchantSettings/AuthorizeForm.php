<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\MerchantSettings;

use App\Enums\MerchantType;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AuthorizeForm extends Form
{
    public array $merchant_type = [];

    public string $authorize_login_id = '';

    public string $authorize_transaction_key = '';

    public function setData(Collection $merchants): void
    {
        $this->fill([
            'merchant_type' => $merchants->pluck('merchant_type')->map(fn (MerchantType $merchantType) => $merchantType->value)->toArray(),
            'authorize_login_id' => $merchants->first()->authorize_login_id,
            'authorize_transaction_key' => $merchants->first()->authorize_transaction_key,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'merchant_type' => ['required', 'array', Rule::in(MerchantType::values())],
            'authorize_login_id' => ['required', 'string'],
            'authorize_transaction_key' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'merchant_type' => __('Please select at least one payment method.'),
        ];
    }
}
