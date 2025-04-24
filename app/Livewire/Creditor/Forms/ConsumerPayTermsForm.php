<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Models\Consumer;
use App\Models\Group;
use App\Models\Subclient;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ConsumerPayTermsForm extends Form
{
    public ?Consumer $consumer = null;

    public ?Subclient $subclient = null;

    public ?Group $group = null;

    public $pif_discount_percent = '';

    public $pif_balance_discount_percent = '';

    public $pay_setup_discount_percent = '';

    public $ppa_balance_discount_percent = '';

    public $min_monthly_pay_percent = '';

    public $max_days_first_pay = '';

    public $minimum_settlement_percentage = '';

    public $minimum_payment_plan_percentage = '';

    public $max_first_pay_days = '';

    public function setData(Subclient|Consumer|Group $record): void
    {
        $this->consumer = $record instanceof Consumer ? $record : null;
        $this->subclient = $record instanceof Subclient ? $record : null;
        $this->group = $record instanceof Group ? $record : null;

        $this->fill([
            'pif_discount_percent' => $record->pif_discount_percent ?? '',
            'pif_balance_discount_percent' => $record->pif_balance_discount_percent ?? '',
            'pay_setup_discount_percent' => $record->pay_setup_discount_percent ?? '',
            'ppa_balance_discount_percent' => $record->ppa_balance_discount_percent ?? '',
            'min_monthly_pay_percent' => $record->min_monthly_pay_percent ?? '',
            'max_days_first_pay' => $record->max_days_first_pay ?? '',
            'minimum_settlement_percentage' => $record->minimum_settlement_percentage ?? '',
            'minimum_payment_plan_percentage' => $record->minimum_payment_plan_percentage ?? '',
            'max_first_pay_days' => $record->max_first_pay_days ?? '',
        ]);
    }

    public function rules(): array
    {
        $commonRules = ['integer', 'max:99', 'regex:/^\d+$/'];

        $ltFieldForPif = match (true) {
            filled($this->pif_discount_percent) => 'pif_discount_percent',
            filled($this->pif_balance_discount_percent) => 'pif_balance_discount_percent',
            default => null,
        };

        return [
            'pif_discount_percent' => ['nullable', Rule::RequiredIf(fn () => $this->consumer), 'min:2', ...$commonRules],
            'pif_balance_discount_percent' => ['nullable', Rule::RequiredIf(fn () => ($this->group || $this->subclient)), 'min:2', ...$commonRules],
            'pay_setup_discount_percent' => [
                Rule::requiredIf(fn () => $this->consumer),
                'min:1',
                ...$commonRules,
            ],
            'ppa_balance_discount_percent' => [
                Rule::requiredIf(fn () => ($this->subclient || $this->group)),
                'min:1',
                ...$commonRules,
            ],
            'min_monthly_pay_percent' => [
                'required',
                'min:2',
                ...$commonRules,
            ],
            'max_days_first_pay' => ['required', 'integer', 'min:1', 'max:1000'],
            'minimum_settlement_percentage' => ['required', 'min:1', ...$commonRules, 'lt:' . $ltFieldForPif],
            'minimum_payment_plan_percentage' => ['required', 'min:1', ...$commonRules, 'lt:min_monthly_pay_percent'],
            'max_first_pay_days' => ['required', 'integer', 'min:1', 'max:1000', 'gt:max_days_first_pay'],
        ];
    }
}
