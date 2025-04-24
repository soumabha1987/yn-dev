<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AccountProfile;

use App\Enums\CompanyStatus;
use App\Models\User;
use App\Services\CompanyMembershipService;
use App\Services\MembershipTransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.profile-steps-layout')]
class IndexPage extends Component
{
    public string $currentStep = 'creditor.account-profile.company-profile';

    public array $steps = [
        'creditor.account-profile.company-profile',
        'creditor.account-profile.membership-plan',
        'creditor.account-profile.billing-details',
    ];

    public array $completedSteps = [];

    private User $user;

    private array $methods = [
        'isProfileCompleted',
        'hasMembership',
        'hasBillingDetails',
    ];

    public function __construct()
    {
        $this->user = Auth::user();

        $this->user->loadMissing('company.activeCompanyMembership');
    }

    public function mount(): void
    {
        $this->checkProfileIsCompleted();

        $this->gotoCurrentStep();
    }

    public function cardTitle(string $currentStep): ?string
    {
        return match ($currentStep) {
            'creditor.account-profile.company-profile' => __('Company Profile'),
            'creditor.account-profile.membership-plan' => __('Choose Membership Plan'),
            'creditor.account-profile.billing-details' => __('Membership Billing Details'),
            default => null,
        };
    }

    #[On('next')]
    public function next(): void
    {
        $currentStepIndex = array_search($this->currentStep, $this->steps, strict: true);

        if (! in_array($this->currentStep, $this->completedSteps)) {
            array_push($this->completedSteps, $this->currentStep);
        }

        $this->currentStep = $this->steps[min($currentStepIndex + 1, count($this->steps) - 1)];
    }

    #[On('previous')]
    public function previous(): void
    {
        $currentStepIndex = array_search($this->currentStep, $this->steps);

        $this->currentStep = $this->steps[max(0, $currentStepIndex - 1)];
    }

    public function switchStep(int $stepIndex): void
    {
        $this->currentStep = $this->steps[min($stepIndex, count($this->steps) - 1)];
    }

    protected function checkProfileIsCompleted(): void
    {
        $membershipTransactionExists = app(MembershipTransactionService::class)->isSuccessExistsOfCompany($this->user->company_id);

        if (
            $membershipTransactionExists
            && $this->user->company->activeCompanyMembership !== null
            && in_array($this->user->company->status, [CompanyStatus::ACTIVE, CompanyStatus::SUBMITTED])
        ) {
            $this->redirectRoute('creditor.settings', navigate: true);

            return;
        }

        if ($membershipTransactionExists && $this->user->company->activeCompanyMembership !== null) {
            $this->redirectRoute('creditor.profile', navigate: true);
        }
    }

    protected function gotoCurrentStep(): void
    {
        collect($this->steps)
            ->combine($this->methods)
            ->filter(fn (string $method, string $step) => ! in_array($step, $this->completedSteps))
            ->each(function (string $method, string $step) {
                if ($this->$method()) {
                    array_push($this->completedSteps, $step);
                }
            });

        if (filled($this->completedSteps)) {
            $lastCompletedStepIndex = collect($this->steps)->search(last($this->completedSteps), strict: true);

            $nextStepIndex = min($lastCompletedStepIndex + 1, count($this->steps) - 1);

            $this->currentStep = $this->steps[$nextStepIndex];
        }
    }

    protected function isProfileCompleted(): bool
    {
        $requiredAttributes = collect([
            'company_name',
            'owner_full_name',
            'owner_email',
            'owner_phone',
            'business_category',
            'timezone', 'from_time',
            'to_time',
            'from_day',
            'to_day',
            'url',
            'address',
            'city',
            'state',
            'zip',
        ]);

        return $requiredAttributes->every(fn ($attribute) => filled($this->user->company->{$attribute}));
    }

    protected function hasMembership(): bool
    {
        return app(CompanyMembershipService::class)->hasInActiveMembership($this->user->company_id);
    }

    protected function hasBillingDetails(): bool
    {
        return app(MembershipTransactionService::class)->isSuccessExistsOfCompany($this->user->company_id);
    }

    public function render(): View
    {
        return view('livewire.creditor.account-profile.index-page')
            ->title(__('Account Profile'));
    }
}
