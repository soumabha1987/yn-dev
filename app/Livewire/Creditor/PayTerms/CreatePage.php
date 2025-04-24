<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\PayTerms;

use App\Enums\Role;
use App\Livewire\Creditor\Forms\PayTermsForm;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\GroupService;
use App\Services\SetupWizardService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Url;
use Livewire\Component;

class CreatePage extends Component
{
    public PayTermsForm $form;

    #[Url]
    public bool $selectMasterTerms = false;

    #[Url]
    public bool $selectSubAccountTerms = false;

    #[Url]
    public ?int $subAccount;

    public Collection $payTermsOption;

    private ?int $companyId = null;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();

        $this->user->loadMissing(['company', 'subclient']);

        $this->companyId = $this->user->hasRole(Role::CREDITOR)
            ? (int) $this->user->company_id
            : (int) $this->user->subclient_id;
    }

    public function mount(): void
    {
        if ($this->selectMasterTerms) {
            $this->form->fill(['pay_terms' => 'master_terms']);

            $this->form->fillMasterTerms($this->user);
        }

        if ($this->selectSubAccountTerms && $this->subAccount) {
            $this->form->fill(['pay_terms' => $this->subAccount]);
        }
    }

    public function updatedFormPayTerms(): void
    {
        if ($this->form->pay_terms === 'master_terms') {
            $this->form->fillMasterTerms($this->user);

            return;
        }

        $this->form->reset([
            'pif_balance_discount_percent',
            'ppa_balance_discount_percent',
            'min_monthly_pay_percent',
            'max_days_first_pay',
            'minimum_settlement_percentage',
            'minimum_payment_plan_percentage',
            'max_first_pay_days',
        ]);

        $this->resetValidation();
    }

    public function save(): void
    {
        $validatedData = $this->form->validate();

        [$selectedType, $selectedId] = $this->form->pay_terms === 'master_terms' ? ['master_terms', null] : explode('_', $this->form->pay_terms);

        if (
            $this->form->pay_terms !== 'master_terms'
            && blank($validatedData['pif_balance_discount_percent'])
            && blank($validatedData['ppa_balance_discount_percent'])
            && blank($validatedData['min_monthly_pay_percent'])
            && blank($validatedData['max_days_first_pay'])
            && blank($validatedData['minimum_settlement_percentage'])
            && blank($validatedData['minimum_payment_plan_percentage'])
            && blank($validatedData['max_first_pay_days'])
        ) {
            $this->error(__('At least one field is required.'));

            return;
        }

        $setupWizardService = app(SetupWizardService::class);

        $isNotCompletedSetupWizard = $setupWizardService->cachingRemainingRequireStepCount($this->user) !== 0;

        if (
            $isNotCompletedSetupWizard
            && $setupWizardService->isLastRequiredStepRemaining($this->user)
            && $this->form->pay_terms === 'master_terms'
        ) {
            Session::put('show-wizard-completed-modal', true);
            Cache::forget('remaining-wizard-required-steps-' . $this->user->id);
        }

        match ($selectedType) {
            'master_terms' => app(CompanyService::class)->updateTerms($this->user->company_id, $validatedData),
            'group' => app(GroupService::class)->updateTerms((int) $selectedId, $validatedData),
            'subclient' => app(SubclientService::class)->updateTerms((int) $selectedId, $validatedData),
            default => null,
        };

        $this->success(__('Pay term offer created.'));

        $isOnlyMasterTerms = $this->payTermsOption->count() === 1;

        if ($isNotCompletedSetupWizard && $isOnlyMasterTerms) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->redirectRoute('creditor.pay-terms', navigate: true);
    }

    public function render(): View
    {
        $groupTerms = app(GroupService::class)
            ->fetchTermsNameAndId($this->companyId)
            ->prepend('master terms (minimum requirement)', 'master_terms');

        $subclientTerms = app(SubclientService::class)
            ->fetchTermsNameAndId($this->companyId);

        $this->payTermsOption = $groupTerms->merge($subclientTerms);

        return view('livewire.creditor.pay-terms.create-page')
            ->title(__('Create Pay Term Offer Profile(s)'));
    }
}
