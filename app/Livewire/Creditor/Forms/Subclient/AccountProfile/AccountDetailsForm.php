<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Subclient\AccountProfile;

use App\Enums\CompanyCategory;
use App\Enums\IndustryType;
use App\Enums\State;
use App\Models\Subclient;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AccountDetailsForm extends Form
{
    public ?Subclient $subclient;

    public bool $use_company_master_terms = false;

    public bool $use_company_merchant = false;

    public string $subclient_name = '';

    public string $email = '';

    public string $phone = '';

    public string $industry_type = '';

    public string $company_category = '';

    public string $fed_tax_id = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public function setup(Subclient $subclient): void
    {
        $this->fill([
            'subclient' => $subclient,
            'use_company_master_terms' => Session::get('use_company_master_terms', false),
            'use_company_merchant' => Session::get('use_company_merchant', false),
            'subclient_name' => $subclient->subclient_name ?? '',
            'email' => $subclient->email ?? '',
            'phone' => $subclient->phone ?? '',
            'industry_type' => $subclient->industry_type->value ?? '',
            'company_category' => $subclient->company_category->value ?? '',
            'fed_tax_id' => $subclient->fed_tax_id ?? '',
            'address' => $subclient->address ?? '',
            'city' => $subclient->city ?? '',
            'state' => $subclient->state ?? '',
            'zip' => $subclient->zip ?? '',
        ]);
    }

    public function rules(): array
    {
        return [
            'use_company_master_terms' => ['required', 'boolean'],
            'use_company_merchant' => ['required', 'boolean'],
            'subclient_name' => ['required', 'string', 'max:50', Rule::unique(Subclient::class)->ignore($this->subclient?->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Subclient::class)->ignore($this->subclient?->id)],
            'phone' => ['required', 'phone:US'],
            'industry_type' => ['required', Rule::in(IndustryType::values())],
            'company_category' => ['required', Rule::in(CompanyCategory::values())],
            'fed_tax_id' => ['sometimes', 'string', 'max:9'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', Rule::in(State::values())],
            'zip' => ['required', 'string', 'max:255'],
        ];
    }
}
