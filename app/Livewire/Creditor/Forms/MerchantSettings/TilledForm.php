<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\MerchantSettings;

use App\Enums\BankAccountType;
use App\Enums\CompanyCategory;
use App\Enums\IndustryType;
use App\Enums\Role;
use App\Enums\YearlyVolumeRange;
use App\Models\Company;
use App\Models\Subclient;
use App\Models\User;
use App\Rules\AddressSingleSpace;
use App\Rules\AgeBetween18And100Rule;
use App\Rules\AlphaNumberSingleSpace;
use App\Rules\AlphaSingleSpace;
use App\Rules\NamingRule;
use App\Rules\RoutingNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TilledForm extends Form
{
    public ?string $account_holder_name = '';

    public ?string $bank_name = '';

    public ?string $bank_account_type = '';

    public ?string $bank_account_number = '';

    public ?string $bank_routing_number = '';

    public ?string $fed_tax_id = '';

    public ?string $legal_name = '';

    public ?string $industry_type = '';

    public ?string $company_category = '';

    public ?string $average_transaction_amount = '';

    public ?string $statement_descriptor = '';

    public ?string $yearly_volume_range = '';

    public ?string $first_name = '';

    public ?string $ssn = '';

    public ?string $last_name = '';

    public ?string $dob = '';

    public ?string $job_title = '';

    public ?string $percentage_shareholding = '';

    public ?string $contact_address = '';

    public ?string $contact_state = '';

    public ?string $contact_city = '';

    public ?string $contact_zip = '';

    public function setData(Company|Subclient $account): void
    {
        $nameParts = explode(' ', $account->company_name ?? '');

        $this->fill([
            'account_holder_name' => $account->account_holder_name,
            'bank_name' => $account->bank_name,
            'bank_account_type' => $account->bank_account_type->value ?? '',
            'fed_tax_id' => $account->fed_tax_id,
            'legal_name' => $account->legal_name ?? $account->company_name ?? '',
            'industry_type' => $account->industry_type->value ?? '',
            'company_category' => $account->company_category->value ?? '',
            'average_transaction_amount' => $account->average_transaction_amount,
            'statement_descriptor' => $account->statement_descriptor,
            'yearly_volume_range' => $account->yearly_volume_range->value ?? '',
            'first_name' => $account->first_name ?? $nameParts[0],
            'last_name' => $account->last_name ?? $nameParts[1] ?? '',
            'dob' => $account->dob?->toDateString(),
            'job_title' => $account->job_title,
            'percentage_shareholding' => $account->percentage_shareholding,
            'contact_address' => $account->owner_address ?? $account->billing_address ?? '',
            'contact_state' => $account->owner_state ?? $account->billing_state ?? '',
            'contact_city' => $account->owner_city ?? $account->billing_city ?? '',
            'contact_zip' => $account->owner_zip ?? $account->billing_zip ?? '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $rules = [
            'account_holder_name' => ['required', 'min:2', 'max:50', new NamingRule],
            'bank_name' => ['required', 'min:2', 'max:50', new AlphaNumberSingleSpace],
            'bank_account_type' => ['required', Rule::in(BankAccountType::values())],
            'bank_account_number' => ['required', 'numeric', 'min_digits:4', 'max_digits:17'],
            'bank_routing_number' => ['required', 'numeric', 'digits:9', new RoutingNumber],
        ];

        if ($user->hasRole(Role::CREDITOR) && $user->company->tilled_profile_completed_at) {
            return $rules;
        }

        return [
            ...$rules,
            'average_transaction_amount' => ['required', 'numeric', 'min:1', 'regex:/^\d+$/'],
            'fed_tax_id' => ['required', 'numeric', 'digits:9'],
            'legal_name' => ['required', 'min:2', 'max:100', new AlphaNumberSingleSpace],
            'industry_type' => ['required', 'max:30', Rule::in(IndustryType::values())],
            'company_category' => ['required', 'max:30', Rule::in(CompanyCategory::values())],
            'statement_descriptor' => ['required', 'min:2', 'max:20', new AlphaNumberSingleSpace],
            'yearly_volume_range' => ['required', Rule::in(YearlyVolumeRange::values())],
            'first_name' => ['required', 'min:2', 'max:20', new NamingRule],
            'ssn' => [
                Rule::requiredIf(! in_array($this->industry_type, IndustryType::ssnIsNotRequired())),
                'numeric',
                'digits:9',
            ],
            'last_name' => ['required', 'min:2', 'max:30', new NamingRule],
            'dob' => ['required', 'date', new AgeBetween18And100Rule],
            'job_title' => ['required', 'string', 'min:2', 'max:30', new AlphaSingleSpace],
            'percentage_shareholding' => ['required', 'numeric', 'min:1', 'max:100', 'regex:/^\d+$/'],
            'contact_address' => ['required', 'string', 'min:2', 'max:100', new AddressSingleSpace],
            'contact_city' => ['required', 'string', 'min:2', 'max:30', new AlphaSingleSpace],
            'contact_state' => ['required', 'string', 'max:2'],
            'contact_zip' => ['required', 'numeric', 'max_digits:10'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'fed_tax_id' => 'tax identification number',
            'dob' => 'DOB',
            'contact_zip' => 'contact zip code',
            'industry_type' => 'business type',
        ];
    }
}
