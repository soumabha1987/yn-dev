<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ConsumerPayTerms;

use App\Enums\CommunicationCode;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Creditor\Forms\ConsumerPayTermsForm;
use App\Models\Consumer;
use App\Models\Group;
use App\Models\Subclient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Component;

class UpdatePage extends Component
{
    public Subclient|Consumer|Group $record;

    public ConsumerPayTermsForm $form;

    public bool $isMenuItem = false;

    public function mount(): void
    {
        $this->form->setData($this->record);
    }

    public function update(): void
    {
        $validatedData = $this->form->validate();
        if ($this->record instanceof Subclient || $this->record instanceof Group) {
            $validatedData['pif_balance_discount_percent'] = filled($validatedData['pif_balance_discount_percent']) ? $validatedData['pif_balance_discount_percent'] : null;
            $validatedData['ppa_balance_discount_percent'] = filled($validatedData['ppa_balance_discount_percent']) ? $validatedData['ppa_balance_discount_percent'] : null;
            Arr::forget($validatedData, ['pif_discount_percent', 'pay_setup_discount_percent']);
        }

        if ($this->record instanceof Consumer) {
            $validatedData['pif_discount_percent'] = filled($validatedData['pif_discount_percent']) ? $validatedData['pif_discount_percent'] : null;
            $validatedData['pay_setup_discount_percent'] = filled($validatedData['pay_setup_discount_percent']) ? $validatedData['pay_setup_discount_percent'] : null;
            Arr::forget($validatedData, ['pif_balance_discount_percent', 'ppa_balance_discount_percent']);
        }

        $validatedData['min_monthly_pay_percent'] = filled($validatedData['min_monthly_pay_percent']) ? $validatedData['min_monthly_pay_percent'] : null;
        $validatedData['max_days_first_pay'] = filled($validatedData['max_days_first_pay']) ? $validatedData['max_days_first_pay'] : null;
        $validatedData['minimum_settlement_percentage'] = filled($validatedData['minimum_settlement_percentage']) ? $validatedData['minimum_settlement_percentage'] : null;
        $validatedData['minimum_payment_plan_percentage'] = filled($validatedData['minimum_payment_plan_percentage']) ? $validatedData['minimum_payment_plan_percentage'] : null;
        $validatedData['max_first_pay_days'] = filled($validatedData['max_first_pay_days']) ? $validatedData['max_first_pay_days'] : null;

        $this->record->update($validatedData);

        $this->success(__(':record offer updated.', [
            'record' => match (true) {
                $this->record instanceof Subclient => 'Subclient',
                $this->record instanceof Consumer => 'Consumer',
                $this->record instanceof Group => 'Group',
            },
        ]));

        TriggerEmailAndSmsServiceJob::dispatchIf($this->record instanceof Consumer, $this->record, CommunicationCode::UPDATE_PAY_TERMS_OFFER);

        $this->dispatch('close-dialog');
    }

    public function render(): View
    {
        return view('livewire.creditor.consumer-pay-terms.update-page')
            ->with('isConsumerPayTerms', $this->record instanceof Consumer);
    }
}
