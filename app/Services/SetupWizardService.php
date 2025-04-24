<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SetupWizardService
{
    public function __construct(
        protected CustomContentService $customContentService,
    ) {}

    public function getRemainingStepsCount(User $user): int
    {
        $remainingRequiredSteps = 0;

        if ($this->isCompanyInactiveOrNotVerified($user)) {
            $remainingRequiredSteps++;
        }

        if ($this->isCsvHeaderMissing($user)) {
            $remainingRequiredSteps++;
            if ($this->hasReachedMaxSteps($remainingRequiredSteps)) {
                return $remainingRequiredSteps;
            }
        }

        if ($this->isCompanyDiscountDataIncomplete($user->company)) {
            $remainingRequiredSteps++;

            if ($this->hasReachedMaxSteps($remainingRequiredSteps)) {
                return $remainingRequiredSteps;
            }
        }

        if ($this->customContentService->defaultTermsAndConditionDoesntExists($user)) {
            $remainingRequiredSteps++;

            if ($this->hasReachedMaxSteps($remainingRequiredSteps)) {
                return $remainingRequiredSteps;
            }
        }

        if ($this->customContentService->defaultAboutUsDoesntExists($user)) {
            $remainingRequiredSteps++;

            if ($this->hasReachedMaxSteps($remainingRequiredSteps)) {
                return $remainingRequiredSteps;
            }
        }

        return $remainingRequiredSteps;
    }

    public function cachingRemainingRequireStepCount(User $user): int
    {
        return Cache::remember(
            'remaining-wizard-required-steps-' . $user->id,
            now()->addHour(),
            fn () => $this->getRemainingStepsCount($user)
        );
    }

    public function isRequiredStepsCompleted(User $user, Company $company): bool
    {
        return (! $this->isCompanyInactiveOrNotVerified($user))
            && (! $this->isCompanyDiscountDataIncomplete($company))
            && (! $this->customContentService->defaultTermsAndConditionDoesntExists($user))
            && (! $this->isCsvHeaderMissing($user))
            && (! $this->customContentService->defaultAboutUsDoesntExists($user));
    }

    private function isCompanyInactiveOrNotVerified(User $user): bool
    {
        return $user->company->status !== CompanyStatus::ACTIVE
            || app(MerchantService::class)->getVerifiedOfCompany($user->company_id)->isEmpty();
    }

    private function isCsvHeaderMissing(User $user): bool
    {
        return ! app(CsvHeaderService::class)
            ->isExists($user->subclient_id, $user->company_id);
    }

    private function hasReachedMaxSteps(int $remainingRequiredSteps): bool
    {
        return $remainingRequiredSteps >= 2;
    }

    private function isCompanyDiscountDataIncomplete(Company $company): bool
    {
        return blank($company->pif_balance_discount_percent)
        && blank($company->ppa_balance_discount_percent)
        && blank($company->min_monthly_pay_percent)
        && blank($company->max_days_first_pay);
    }

    public function isLastRequiredStepRemaining(User $user): bool
    {
        return $this->getRemainingStepsCount($user) === 1;
    }
}
