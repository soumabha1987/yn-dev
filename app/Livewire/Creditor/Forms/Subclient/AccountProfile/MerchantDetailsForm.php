<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Subclient\AccountProfile;

use App\Enums\BankAccountType;
use App\Enums\IndustryType;
use App\Enums\YearlyVolumeRange;
use App\Models\Subclient;
use App\Rules\AgeBetween18And100Rule;
use App\Rules\RoutingNumber;
use Illuminate\Validation\Rule;
use Livewire\Form;

class MerchantDetailsForm extends Form
{
    public ?Subclient $subclient;

    public string $account_holder_name = '';

    public string $bank_name = '';

    public string $bank_account_type = '';

    public string $bank_account_number = '';

    public string $bank_routing_number = '';

    public string $legal_name = '';

    public string $statement_descriptor = '';

    public string $yearly_volume_range = '';

    public string $fed_tax_id = '';

    public string $average_transaction_amount = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $dob = '';

    public string $ssn = '';

    public string $job_title = '';

    public string $percentage_shareholding = '';

    public string $owner_full_name = '';

    public string $owner_email = '';

    public string $owner_phone = '';

    public string $owner_address = '';

    public string $owner_city = '';

    public string $owner_state = '';

    public string $owner_zip = '';

    public function setUp(Subclient $subclient): void
    {
        $this->fill([
            'subclient' => $subclient,
            'account_holder_name' => $subclient->account_holder_name ?? '',
            'bank_name' => $subclient->bank_name ?? '',
            'bank_account_type' => $subclient->bank_account_type->value ?? '',
            'bank_account_number' => $subclient->bank_account_number ?? '',
            'bank_routing_number' => $subclient->bank_routing_number ?? '',
            'legal_name' => $subclient->legal_name ?? '',
            'statement_descriptor' => $subclient->statement_descriptor ?? '',
            'yearly_volume_range' => $subclient->yearly_volume_range->value ?? '',
            'fed_tax_id' => $subclient->fed_tax_id ?? '',
            'average_transaction_amount' => $subclient->average_transaction_amount ?? '',
            'first_name' => $subclient->first_name ?? '',
            'last_name' => $subclient->last_name ?? '',
            'dob' => $subclient->dob ?? '',
            'ssn' => $subclient->ssn ?? '',
            'job_title' => $subclient->job_title ?? '',
            'percentage_shareholding' => $subclient->percentage_shareholding ?? '',
            'owner_full_name' => $subclient->owner_full_name ?? '',
            'owner_email' => $subclient->owner_email ?? '',
            'owner_phone' => $subclient->owner_phone ?? '',
            'owner_address' => $subclient->owner_address ?? '',
            'owner_city' => $subclient->owner_city ?? '',
            'owner_state' => $subclient->owner_state ?? '',
            'owner_zip' => $subclient->owner_zip ?? '',
        ]);
    }

    public function rules(): array
    {
        return [
            'account_holder_name' => ['required', 'max:100'],
            'bank_name' => ['required', 'max:100'],
            'bank_account_type' => ['required', Rule::in(BankAccountType::values())],
            'bank_account_number' => ['required', 'numeric', 'min_digits:4', 'max_digits:17'],
            'bank_routing_number' => ['required', 'numeric', 'digits:9', new RoutingNumber],
            'average_transaction_amount' => ['required', 'numeric'],
            'fed_tax_id' => ['required', 'numeric', 'digits:9'],
            'legal_name' => ['required', 'max:100'],
            'statement_descriptor' => ['required', 'max:20'],
            'yearly_volume_range' => ['required', Rule::in(YearlyVolumeRange::values())],
            'first_name' => ['required', 'max:50'],
            'ssn' => [
                Rule::requiredIf(fn () => (in_array($this->subclient->industry_type?->value, IndustryType::ssnIsNotRequired()))),
                'numeric',
                'digits:9',
            ],
            'last_name' => ['required', 'max:50'],
            'dob' => ['required', 'date', new AgeBetween18And100Rule],
            'job_title' => ['required', 'string', 'max:30'],
            'percentage_shareholding' => ['required', 'numeric', 'min:0', 'max:100'],
            'owner_full_name' => ['required', 'max:255'],
            'owner_email' => ['required', 'string', 'email'],
            'owner_phone' => ['required', 'phone:US'],
            'owner_address' => ['required', 'string', 'max:200'],
            'owner_city' => ['required', 'string', 'max:20'],
            'owner_state' => ['required', 'string', 'max:2'],
            'owner_zip' => ['required', 'numeric', 'max_digits:10'],
        ];
    }
}
