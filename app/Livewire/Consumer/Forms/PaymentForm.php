<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Forms;

use App\Enums\BankAccountType;
use App\Enums\MerchantName;
use App\Enums\MerchantType;
use App\Enums\State;
use App\Livewire\Consumer\ExternalPayment;
use App\Livewire\Consumer\Payment;
use App\Models\Consumer;
use App\Models\Merchant;
use App\Rules\RoutingNumber;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PaymentForm extends Form
{
    public string $first_name = '';

    public string $last_name = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public string $method = '';

    public string $card_number = '';

    public string $card_holder_name = '';

    public string $expiry = '';

    public string $cvv = '';

    public string $account_type = '';

    public string $account_number = '';

    public string $routing_number = '';

    public bool $is_terms_accepted = false;

    public bool $is_pif = false;

    public string $payment_method_id = '';

    public array $tilled_response = [];

    public bool $save_card = false;

    public function init(Consumer $consumer, Collection $merchants): void
    {
        $consumer->loadMissing('paymentProfile');

        if ($consumer->paymentProfile !== null) {
            $this->fill([
                'address' => $consumer->paymentProfile->address,
                'city' => $consumer->paymentProfile->city,
                'state' => $consumer->paymentProfile->state,
                'zip' => $consumer->paymentProfile->zip,
                'method' => $consumer->paymentProfile->method->value,
            ]);

            return;
        }

        $this->fill([
            'address' => $consumer->address1 ?? '',
            'city' => $consumer->city ?? '',
            'state' => $consumer->state ?? '',
            'zip' => $consumer->zip ?? '',
            'method' => $merchants->contains('merchant_type', MerchantType::CC) ? MerchantType::CC->value : MerchantType::ACH->value,
        ]);
    }

    public function rules(): array
    {
        /** @var Payment|ExternalPayment $component */
        $component = $this->component;

        $ccRules = [
            'sometimes',
            Rule::requiredIf(
                fn () => $this->method === MerchantType::CC->value
                && $component->merchants->containsStrict(fn (Merchant $merchant) => $merchant->merchant_name !== MerchantName::YOU_NEGOTIATE)
            ),
        ];
        $achRules = [
            'sometimes',
            Rule::requiredIf(
                fn () => $this->method === MerchantType::ACH->value
                && $component->merchants->containsStrict(fn (Merchant $merchant) => $merchant->merchant_name !== MerchantName::YOU_NEGOTIATE)
            ),
        ];

        return [
            'first_name' => ['nullable', Rule::requiredIf($component->isDisplayName), 'string', 'max:255'],
            'last_name' => ['nullable', Rule::requiredIf($component->isDisplayName), 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', Rule::in(State::values())],
            'zip' => ['required', 'string', 'max_digits:5'],
            'method' => ['required', 'string', Rule::in(MerchantType::values())],
            'card_number' => [...$ccRules, 'min_digits:14', 'max_digits:16'],
            'card_holder_name' => [...$ccRules, 'string', 'max:255'],
            'expiry' => [...$ccRules, 'date_format:m/Y'],
            'cvv' => [...$ccRules, 'min_digits:3', 'max_digits:4'],
            'account_type' => [...$achRules, 'string', Rule::in(BankAccountType::values())],
            'account_number' => [...$achRules, 'string', 'numeric'],
            'routing_number' => [...$achRules, 'numeric', 'digits:9', new RoutingNumber],
            'is_terms_accepted' => ['required', 'accepted'],
            'tilled_response' => ['sometimes', 'array'],
            'payment_method_id' => ['sometimes', 'string'],
            'save_card' => ['required', 'boolean']
        ];
    }
}
