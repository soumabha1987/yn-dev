<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Subclient\AccountProfile;

use App\Models\Subclient;
use Livewire\Form;

class MasterTermsForm extends Form
{
    public string $pif_balance_discount_percent = '';

    public string $ppa_balance_discount_percent = '';

    public string $min_monthly_pay_percent = '';

    public string $max_days_first_pay = '';

    public function setup(Subclient $subclient): void
    {
        $this->fill([
            'pif_balance_discount_percent' => $subclient->pif_balance_discount_percent ?? '',
            'ppa_balance_discount_percent' => $subclient->ppa_balance_discount_percent ?? '',
            'min_monthly_pay_percent' => $subclient->min_monthly_pay_percent ?? '',
            'max_days_first_pay' => $subclient->max_days_first_pay ?? '',
        ]);
    }

    public function rules(): array
    {
        return [
            'pif_balance_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'ppa_balance_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_monthly_pay_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_days_first_pay' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }
}
