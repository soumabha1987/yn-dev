<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Subclient;

use App\Models\Subclient;
use Livewire\Form;

class PayTermsForm extends Form
{
    public string $pay_terms = '';

    public $pif_balance_discount_percent = '';

    public $ppa_balance_discount_percent = '';

    public $min_monthly_pay_percent = '';

    public $max_days_first_pay = '';

    public function init(Subclient $subclient): void
    {
        $this->fill([
            'pif_balance_discount_percent' => $subclient->pif_balance_discount_percent,
            'ppa_balance_discount_percent' => $subclient->ppa_balance_discount_percent,
            'min_monthly_pay_percent' => $subclient->min_monthly_pay_percent,
            'max_days_first_pay' => $subclient->max_days_first_pay,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pif_balance_discount_percent' => ['required', 'integer', 'min:0', 'max:100', 'regex:/^\d+$/'],
            'ppa_balance_discount_percent' => ['required', 'integer', 'min:0', 'max:100', 'regex:/^\d+$/'],
            'min_monthly_pay_percent' => ['required', 'integer', 'min:0', 'max:100', 'regex:/^\d+$/'],
            'max_days_first_pay' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }
}
