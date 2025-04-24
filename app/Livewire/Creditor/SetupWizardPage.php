<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use App\Enums\CompanyStatus;
use App\Models\User;
use App\Services\CsvHeaderService;
use App\Services\CustomContentService;
use App\Services\MerchantService;
use App\Services\PersonalizedLogoService;
use App\Services\SftpConnectionService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SetupWizardPage extends Component
{
    public bool $step1IsCompleted = false;

    public bool $step2IsCompleted = false;

    public bool $step3IsCompleted = false;

    public bool $step4IsCompleted = false;

    public bool $step5IsCompleted = false;

    public bool $step6IsCompleted = false;

    public bool $step7IsCompleted = false;

    public bool $step8IsCompleted = false;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->user->loadMissing('company');
    }

    public function mount(): void
    {
        $customContentService = app(CustomContentService::class);

        $this->js("localStorage.setItem('dashboardWarningModal', true)");

        $this->step1IsCompleted = app(MerchantService::class)->getVerifiedOfCompany($this->user->company_id)->isNotEmpty();

        $this->step2IsCompleted = app(SubclientService::class)->isExists($this->user->company_id);
        $this->step3IsCompleted = filled($this->user->company->pif_balance_discount_percent)
            && filled($this->user->company->ppa_balance_discount_percent)
            && filled($this->user->company->min_monthly_pay_percent)
            && $this->user->company->max_days_first_pay;
        $this->step4IsCompleted = ! $customContentService->defaultTermsAndConditionDoesntExists($this->user);
        $this->step5IsCompleted = app(CsvHeaderService::class)->isExists($this->user->subclient_id, $this->user->company_id);
        $this->step6IsCompleted = app(SftpConnectionService::class)->enabledExists($this->user->company_id);
        $this->step7IsCompleted = app(PersonalizedLogoService::class)->companyHasPersonalizedLogo($this->user->subclient_id, $this->user->company_id);
        $this->step8IsCompleted = ! $customContentService->defaultAboutUsDoesntExists($this->user);

        if ($this->step1IsCompleted && $this->step2IsCompleted && $this->step3IsCompleted && $this->step4IsCompleted && $this->step5IsCompleted && $this->step6IsCompleted && $this->step7IsCompleted && $this->step8IsCompleted && ($this->user->company->status === CompanyStatus::ACTIVE)) {
            $this->js("localStorage.setItem('dashboardWarningModal', false)");
        }
    }

    public function render(): View
    {
        return view('livewire.creditor.setup-wizard-page')
            ->title(__('How we can help you?'));
    }
}
