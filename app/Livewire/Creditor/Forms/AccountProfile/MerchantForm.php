<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\AccountProfile;

use App\Enums\MerchantName;
use App\Enums\MerchantType;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Form;

class MerchantForm extends Form
{
    public string $merchant_name = '';

    public string $api_key = '';

    public string $secret_key = '';

    public array $merchant_type = [];

    public string $current_merchant_name = '';

    public function init(Collection $merchants): void
    {
        $merchants->whenNotEmpty(function (Collection $merchants): void {
            $this->merchant_name = $merchants->first()->merchant_name->value;
            $this->current_merchant_name = $merchants->first()->merchant_name->value;

            $fillForm = match ($this->merchant_name) {
                MerchantName::AUTHORIZE->value => function () use ($merchants) {
                    $this->fill([
                        'merchant_type' => $merchants->pluck('merchant_type')->map(fn (MerchantType $merchantType) => $merchantType->value)->toArray(),
                        'api_key' => $merchants->first()->authorize_login_id,
                        'secret_key' => $merchants->first()->authorize_transaction_key,
                    ]);
                },
                MerchantName::USA_EPAY->value => function () use ($merchants) {
                    $this->fill([
                        'merchant_type' => $merchants->pluck('merchant_type')->map(fn (MerchantType $merchantType) => $merchantType->value)->toArray(),
                        'api_key' => $merchants->first()->usaepay_key,
                        'secret_key' => $merchants->first()->usaepay_pin,
                    ]);
                },
                MerchantName::STRIPE->value => fn () => $this->fill(['secret_key' => $merchants->first()->stripe_secret_key]),
                default => fn () => null,
            };

            $fillForm();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'merchant_name' => ['required', Rule::in(MerchantName::values())],
            'merchant_type' => [
                'sometimes',
                Rule::requiredIf(fn () => in_array($this->merchant_name, MerchantName::filterACHAndCCMerchants())),
                'array',
                Rule::in(MerchantType::values()),
            ],
            'api_key' => [
                'sometimes',
                Rule::requiredIf(fn () => in_array($this->merchant_name, MerchantName::filterACHAndCCMerchants())),
                'string',
            ],
            'secret_key' => [
                'sometimes',
                Rule::requiredIf(fn () => $this->merchant_name !== '' && $this->merchant_name !== MerchantName::YOU_NEGOTIATE->value),
                'string',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'merchant_name.required' => __('Please select merchant.'),
        ];
    }
}
