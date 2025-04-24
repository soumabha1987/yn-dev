<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\AccountProfile;

use App\Enums\CompanyBusinessCategory;
use App\Enums\DebtType;
use App\Enums\State;
use App\Enums\Timezone;
use App\Models\Company;
use App\Rules\AddressSingleSpace;
use App\Rules\AlphaSingleSpace;
use App\Rules\NamingRule;
use App\Rules\ValidUrl;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CompanyProfileForm extends Form
{
    public string $company_name = '';

    public string $owner_full_name = '';

    public string $owner_email = '';

    public string $owner_phone = '';

    public string $business_category = '';

    public string $debt_type = '';

    public string $fed_tax_id = '';

    public string $timezone = '';

    public string $from_time = '';

    public string $to_time = '';

    public string $from_day = '';

    public string $to_day = '';

    public string $url = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public function init(Company $company): void
    {
        $this->fill([
            'company_name' => $company->company_name ?? '',
            'owner_full_name' => $company->owner_full_name ?? '',
            'owner_email' => $company->owner_email ?? '',
            'owner_phone' => $company->owner_phone ?? '',
            'business_category' => $company->business_category->value ?? '',
            'debt_type' => $company->debt_type->value ?? '',
            'fed_tax_id' => $company->fed_tax_id ?? '',
            'timezone' => $company->timezone->value ?? '',
            'from_time' => $company->from_time?->setTimezone($company->timezone->value)->format('g:i A') ?? '',
            'to_time' => $company->to_time?->setTimezone($company->timezone->value)->format('g:i A') ?? '',
            'from_day' => $company->from_day ?? '',
            'to_day' => $company->to_day ?? '',
            'url' => $company->url ?? '',
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
            'company_name' => ['required', 'string', 'min:3', 'max:50', new NamingRule],
            'owner_full_name' => ['required', 'string', 'min:3', 'max:25', new NamingRule],
            'owner_email' => ['required', 'email', 'max:50'],
            'owner_phone' => ['required', 'phone:US'],
            'business_category' => ['required', 'max:100', Rule::in(CompanyBusinessCategory::values())],
            'debt_type' => ['required', 'max:50', Rule::in(DebtType::values())],
            'fed_tax_id' => ['nullable', 'numeric', 'digits:9'],
            'timezone' => ['required', 'max:5', Rule::in(Timezone::values())],
            'from_time' => ['required', 'date_format:g:i A'],
            'to_time' => ['required', 'date_format:g:i A'],
            'from_day' => ['required', 'integer'],
            'to_day' => ['required', 'integer'],
            'url' => ['required', new ValidUrl],
            'address' => ['required', 'string', 'min:2', 'max:100', new AddressSingleSpace],
            'city' => ['required', 'string', 'min:2', 'max:30', new AlphaSingleSpace],
            'state' => ['required', 'max:10', Rule::in(State::values())],
            'zip' => ['required', 'string', 'numeric', 'max_digits:5'],
        ];
    }
}
