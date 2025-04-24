<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Livewire\Creditor\PayTerms\EditPage;
use App\Models\User;
use App\Services\GroupService;
use App\Services\SubclientService;
use Livewire\Form;

class PayTermsForm extends Form
{
    public string $pay_terms = '';

    public $pif_balance_discount_percent = '';

    public $ppa_balance_discount_percent = '';

    public $min_monthly_pay_percent = '';

    public $max_days_first_pay = '';

    public $minimum_settlement_percentage = '';

    public $minimum_payment_plan_percentage = '';

    public $max_first_pay_days = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pay_terms' => ['required', 'string'],
            'pif_balance_discount_percent' => [
                'required',
                'integer',
                'min:2',
                'max:99',
                'regex:/^\d+$/',
            ],
            'ppa_balance_discount_percent' => [
                'required',
                'integer',
                'min:1',
                'max:99',
                'regex:/^\d+$/',
            ],
            'min_monthly_pay_percent' => [
                'required',
                'integer',
                'min:2',
                'max:99',
                'regex:/^\d+$/',
            ],
            'max_days_first_pay' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
            'minimum_settlement_percentage' => [
                'required',
                'integer',
                'min:1',
                'max:99',
                'lt:pif_balance_discount_percent',
            ],
            'minimum_payment_plan_percentage' => [
                'required',
                'integer',
                'min:1',
                'max:99',
                'lt:min_monthly_pay_percent',
            ],
            'max_first_pay_days' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
                'gt:max_days_first_pay',
            ],
        ];
    }

    public function fillMasterTerms(User $user, bool $isEditTerms = false): void
    {
        if ($isEditTerms) {
            /** @var EditPage $component */
            $component = $this->component;

            $component->payTermsOption = ['master_terms' => 'master terms (minimum requirement)'];
        }

        $this->fill([
            'pay_terms' => 'master_terms',
            'pif_balance_discount_percent' => $user->company->pif_balance_discount_percent,
            'ppa_balance_discount_percent' => $user->company->ppa_balance_discount_percent,
            'min_monthly_pay_percent' => $user->company->min_monthly_pay_percent,
            'max_days_first_pay' => $user->company->max_days_first_pay,
            'minimum_settlement_percentage' => $user->company->minimum_settlement_percentage,
            'minimum_payment_plan_percentage' => $user->company->minimum_payment_plan_percentage,
            'max_first_pay_days' => $user->company->max_first_pay_days,
        ]);
    }

    public function fillSubAccountTerms(int $subclientId, int $companyId): void
    {
        $subclient = app(SubclientService::class)->fetchSubclientTerms($subclientId, $companyId);

        /** @var EditPage $component */
        $component = $this->component;

        $component->payTermsOption = ['subclient_' . $subclient->id => $subclient->subclient_name . '/' . $subclient->unique_identification_number];

        $this->fill([
            'pay_terms' => 'subclient_' . $subclient->id,
            'pif_balance_discount_percent' => $subclient->pif_balance_discount_percent,
            'ppa_balance_discount_percent' => $subclient->ppa_balance_discount_percent,
            'min_monthly_pay_percent' => $subclient->min_monthly_pay_percent,
            'max_days_first_pay' => $subclient->max_days_first_pay,
            'minimum_settlement_percentage' => $subclient->minimum_settlement_percentage,
            'minimum_payment_plan_percentage' => $subclient->minimum_payment_plan_percentage,
            'max_first_pay_days' => $subclient->max_first_pay_days,
        ]);
    }

    public function fillGroupTerms(int $groupId, int $companyId): void
    {
        $group = app(GroupService::class)->fetchGroupTerms($groupId, $companyId);

        /** @var EditPage $component */
        $component = $this->component;

        $component->payTermsOption = ['group_' . $group->id => $group->name];

        $this->fill([
            'pay_terms' => 'group_' . $group->id,
            'pif_balance_discount_percent' => $group->pif_balance_discount_percent,
            'ppa_balance_discount_percent' => $group->ppa_balance_discount_percent,
            'min_monthly_pay_percent' => $group->min_monthly_pay_percent,
            'max_days_first_pay' => $group->max_days_first_pay,
            'minimum_settlement_percentage' => $group->minimum_settlement_percentage,
            'minimum_payment_plan_percentage' => $group->minimum_payment_plan_percentage,
            'max_first_pay_days' => $group->max_first_pay_days,
        ]);
    }
}
