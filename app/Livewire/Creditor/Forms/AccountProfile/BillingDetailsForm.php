<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\AccountProfile;

use App\Enums\State;
use App\Models\Company;
use App\Rules\AddressSingleSpace;
use App\Rules\AlphaSingleSpace;
use Illuminate\Validation\Rule;
use Livewire\Form;

class BillingDetailsForm extends Form
{
    public string $first_name = '';

    public string $last_name = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public bool $acceptTermsAndConditions = false;

    /**
     * @return array<string, mixed>
     */
    public array $tilled_response = [];

    public function fillAddress(Company $company): void
    {
        $this->fill([
            'address' => $company->address ?? '',
            'city' => $company->city ?? '',
            'state' => $company->state ?? '',
            'zip' => $company->zip ?? '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:20', 'alpha:ascii'],
            'last_name' => ['required', 'string', 'min:2', 'max:30', 'alpha:ascii'],
            'address' => ['required', 'string', 'min:2', 'max:100', new AddressSingleSpace],
            'city' => ['required', 'string', 'min:2', 'max:30', new AlphaSingleSpace],
            'state' => ['required', 'max:10', Rule::in(State::values())],
            'zip' => ['required', 'string', 'numeric', 'max_digits:5'],
            'acceptTermsAndConditions' => ['required', 'boolean'],
            'tilled_response' => ['required', 'array'],
            'tilled_response.id' => ['required', 'string'],
            'tilled_response.card.last4' => ['required', 'numeric', 'digits:4'],
            'tilled_response.card.exp_month' => ['required', 'integer', 'min:1', 'max:12', 'max_digits:2'],
            'tilled_response.card.exp_year' => ['required', 'integer', 'digits:4'],
        ];
    }
}
