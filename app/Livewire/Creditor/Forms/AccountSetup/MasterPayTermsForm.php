<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\AccountSetup;

use App\Models\Company;
use Livewire\Form;

class MasterPayTermsForm extends Form
{
    public $pif_balance_discount_percent = '';

    public $ppa_balance_discount_percent = '';

    public $min_monthly_pay_percent = '';

    public $max_days_first_pay = '';

    public function init(Company $company): void
    {
        $this->fill([
            'pif_balance_discount_percent' => $company->pif_balance_discount_percent,
            'ppa_balance_discount_percent' => $company->ppa_balance_discount_percent,
            'min_monthly_pay_percent' => $company->min_monthly_pay_percent,
            'max_days_first_pay' => $company->max_days_first_pay,
        ]);
    }

    public function rules(): array
    {
        return [
            'pif_balance_discount_percent' => ['required', 'integer', 'min:0', 'max:100', 'regex:/^\d+$/'],
            'ppa_balance_discount_percent' => ['required', 'integer', 'min:0', 'max:100', 'regex:/^\d+$/'],
            'min_monthly_pay_percent' => ['required', 'integer', 'min:0', 'max:100', 'regex:/^\d+$/'],
            'max_days_first_pay' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
