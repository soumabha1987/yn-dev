<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\MembershipSettings;

use App\Enums\CompanyMembershipStatus;
use App\Enums\MembershipFeatures;
use App\Enums\MembershipFrequency;
use App\Enums\MembershipTransactionStatus;
use App\Models\CompanyMembership;
use App\Models\Membership;
use App\Models\MembershipPaymentProfile;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Services\MembershipPaymentProfileService;
use App\Services\MembershipService;
use App\Services\MembershipTransactionService;
use App\Services\PartnerService;
use App\Services\TilledPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Page extends Component
{
    public Membership $currentMembership;

    public ?int $differentiateDate = null;

    public ?CompanyMembership $currentCompanyMembership;

    public ?MembershipPaymentProfile $membershipPaymentProfile;

    public bool $dialogOpen = false;

    public bool $isLastTransactionFailed = false;

    public string $tilledErrorMessage = '';

    public bool $isActivePlan = false;

    public bool $cancelledPlanKeepProfile = false;

    public bool $cancelledPlanRemoveProfile = false;

    public string $cancelled_note = '';

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();

        $this->user->loadMissing('company');

        $this->membershipPaymentProfile = app(MembershipPaymentProfileService::class)->fetchByCompany($this->user->company_id);
    }

    public function activePlan(Membership $membership): void
    {
        $this->isActivePlan = true;

        $membershipTransactionStatus = $this->createMembershipTransaction($membership, intval(((float) $membership->price) * 100));

        if ($membershipTransactionStatus === MembershipTransactionStatus::FAILED->value) {
            $this->dispatch('close-confirmation-box');

            return;
        }

        $this->currentCompanyMembership->update([
            'current_plan_end' => match ($membership->frequency) {
                MembershipFrequency::WEEKLY => now()->addWeek(),
                MembershipFrequency::MONTHLY => now()->addMonthNoOverflow(),
                MembershipFrequency::YEARLY => now()->addYear(),
            },
            'membership_id' => $membership->id,
            'status' => CompanyMembershipStatus::ACTIVE,
            'current_plan_start' => now(),
            'auto_renew' => true,
            'next_membership_plan_id' => null,
            'cancelled_at' => null,
        ]);

        $this->updateCompanyRemoveProfileColumn();

        $this->tilledErrorMessage = '';

        $this->success(__('Awesome news, the :planName plan has been updated!', ['planName' => $membership->name]));
    }

    public function nextPlanUpdate(): void
    {
        $this->currentCompanyMembership->update(['next_membership_plan_id' => null]);

        $this->success(__('Your scheduled plan change has been canceled.'));
    }

    public function undoCancelled(): void
    {
        if ($this->currentCompanyMembership->auto_renew) {
            $this->error(__('Sorry your plan already active.'));

            return;
        }

        $this->currentCompanyMembership->update(['auto_renew' => true]);

        $this->updateCompanyRemoveProfileColumn();

        $this->success(__('We have successfully removed your cancellation request!'));
    }

    public function cancelAutoRenewPlan(bool $removeProfile = false): void
    {
        $this->currentCompanyMembership->update(['auto_renew' => false]);

        $this->updateCompanyRemoveProfileColumn($removeProfile);

        $this->cancelledPlanKeepProfile = ! $removeProfile;

        $this->cancelledPlanRemoveProfile = $removeProfile;

        $this->reset('dialogOpen');
    }

    public function submitCancelledNote(): void
    {
        $validatedData = $this->validate(
            ['cancelled_note' => ['nullable', 'string', 'max:250']],
        );

        $this->user->company()->update($validatedData);

        $this->reset('cancelledPlanRemoveProfile');

        $this->reset('dialogOpen');

        $this->success(__('Thank you for your feedback, we are always here to suppport you in any way we can! help@younegotiate.com'));
    }

    public function updateMembership(Membership $membership): void
    {
        $membership->setAttribute('price_per_day', $this->calculatePricePerDay($membership->frequency, (float) $membership->price));
        $this->currentMembership->setAttribute('price_per_day', $this->calculatePricePerDay($this->currentMembership->frequency, (float) $this->currentMembership->price));

        $data = [];

        $isUpgradeOrDowngraded = $membership->getAttribute('price_per_day') > $this->currentMembership->getAttribute('price_per_day')
            ? $this->handleMembershipUpgrade($membership, $data)
            : $this->handleMembershipSameOrDowngrade($membership, $data);

        if ($isUpgradeOrDowngraded) {
            $this->currentCompanyMembership->update($data);

            $this->currentMembership = $data['next_membership_plan_id'] === null ? $membership : $this->currentMembership;

            $this->success(__('Your plan has been successfully updated.'));
        }
    }

    private function updateCompanyRemoveProfileColumn(bool $removeProfile = false): void
    {
        $this->user->company()->update(['remove_profile' => $removeProfile]);
    }

    private function calculatePricePerDay(MembershipFrequency $frequency, int|float $price): float|int
    {
        return match ($frequency) {
            MembershipFrequency::YEARLY => $price / 365,
            MembershipFrequency::MONTHLY => $price / 30,
            MembershipFrequency::WEEKLY => $price / 7,
        };
    }

    private function createMembershipTransaction(Membership $membership, int $amount): string
    {
        $user = Auth::user()->loadMissing('company.partner');

        if (! $this->membershipPaymentProfile) {
            return MembershipTransactionStatus::FAILED->value;
        }

        $response = app(TilledPaymentService::class)->createPaymentIntents($amount, $this->membershipPaymentProfile->tilled_payment_method_id);

        $transactionStatus = optional($response)['status'];

        $membershipTransactionStatus = (! $transactionStatus || ! in_array($transactionStatus, ['processing', 'succeeded']))
            ? MembershipTransactionStatus::FAILED->value
            : MembershipTransactionStatus::SUCCESS->value;

        $planEndDate = $this->currentCompanyMembership->current_plan_end;

        if ($this->isActivePlan) {
            $planEndDate = match ($membership->frequency) {
                MembershipFrequency::WEEKLY => now()->addWeek(),
                MembershipFrequency::MONTHLY => now()->addMonthNoOverflow(),
                MembershipFrequency::YEARLY => now()->addYear(),
            };
            $this->isActivePlan = false;
        }

        $partnerRevenueShare = 0;

        if ($user->company->partner_id) {
            $partnerRevenueShare = app(PartnerService::class)
                ->calculatePartnerRevenueShare($user->company->partner, $amount / 100);
        }

        MembershipTransaction::query()
            ->create([
                'company_id' => $user->company_id,
                'membership_id' => $membership->id,
                'status' => $membershipTransactionStatus,
                'price' => $amount / 100,
                'tilled_transaction_id' => $response['id'] ?? null,
                'response' => $response,
                'plan_end_date' => $planEndDate,
                'partner_revenue_share' => $partnerRevenueShare,
            ]);

        if ($membershipTransactionStatus === MembershipTransactionStatus::FAILED->value) {
            $this->tilledErrorMessage = data_get($response, 'last_payment_error.message', __('We apologize, but the payment for your membership upgrade was unsuccessful.'));

            $this->error($this->tilledErrorMessage);

            $this->dispatch('scroll-tilled-error-message');
        }

        return $membershipTransactionStatus;
    }

    private function handleMembershipSameOrDowngrade(Membership $membership, array &$data): bool
    {
        $data = [
            'next_membership_plan_id' => $membership->id,
            'auto_renew' => true,
        ];

        return true;
    }

    private function handleMembershipUpgrade(Membership $membership, array &$data): bool
    {
        $this->revisePlanDateAndAmount($membership);

        $data = [
            'membership_id' => $membership->id,
            'next_membership_plan_id' => null,
            'auto_renew' => true,
        ];

        if ($membership->getAttribute('new_plan_date') !== null) {
            $data['current_plan_end'] = $membership->getAttribute('new_plan_date');

            return true;
        }

        if ($membership->getAttribute('new_plan_amount') !== null) {
            $createMembershipTransactionStatus = $this->createMembershipTransaction($membership, intval($membership->getAttribute('new_plan_amount') * 100));

            if ($createMembershipTransactionStatus === MembershipTransactionStatus::SUCCESS->value) {
                $this->tilledErrorMessage = '';

                return true;
            }
        }

        return false;
    }

    private function revisePlanDateAndAmount(Membership $membership): void
    {
        $currentMembershipRemainingCredit = $this->currentMembership->getAttribute('price_per_day') * $this->differentiateDate;

        $membershipPricePerDay = $membership->getAttribute('price_per_day');

        $newMembershipAmount = $membershipPricePerDay * $this->differentiateDate;

        if ($membershipPricePerDay > $this->currentMembership->getAttribute('price_per_day')) {
            $membership->setAttribute('new_plan_date', match ($this->currentMembership->frequency) {
                MembershipFrequency::YEARLY => $membership->frequency !== MembershipFrequency::YEARLY
                    ? now()->addDays($currentMembershipRemainingCredit / $membershipPricePerDay)->format('M d, Y')
                    : null,
                MembershipFrequency::MONTHLY => $membership->frequency === MembershipFrequency::WEEKLY
                    ? now()->addDays($currentMembershipRemainingCredit / $membershipPricePerDay)->format('M d, Y')
                    : null,
                default => null,
            });

            $membership->setAttribute('new_plan_amount', match ($this->currentMembership->frequency) {
                MembershipFrequency::YEARLY => $membership->frequency === MembershipFrequency::YEARLY
                    ? $newMembershipAmount - $currentMembershipRemainingCredit
                    : null,
                MembershipFrequency::MONTHLY => $membership->frequency !== MembershipFrequency::WEEKLY
                    ? $newMembershipAmount - $currentMembershipRemainingCredit
                    : null,
                MembershipFrequency::WEEKLY => $newMembershipAmount - $currentMembershipRemainingCredit,
            });
        }
    }

    private function setUp(): Collection
    {
        $memberships = app(MembershipService::class)->membershipsWithPricePerDay();

        /** @var Membership $currentMembership */
        $currentMembership = $memberships->firstWhere(fn (Membership $membership) => $membership->companyMemberships->isNotEmpty());

        $this->currentMembership = $currentMembership;

        $this->currentCompanyMembership = $this->currentMembership->companyMemberships->first();

        if ($this->currentCompanyMembership->current_plan_end->lte(today())) {
            $this->isLastTransactionFailed = app(MembershipTransactionService::class)
                ->isLastFailedTransaction($this->user->company_id, $this->currentMembership->id);
        }

        if ($this->currentCompanyMembership->current_plan_end > now()) {
            $this->isLastTransactionFailed = false;

            $this->differentiateDate = $this->currentCompanyMembership->current_plan_end->diffInDays(now());

            $memberships->each(function ($membership) {
                /** @var Membership $membership */
                if ($membership->id !== $this->currentMembership->id) {
                    $this->revisePlanDateAndAmount($membership);
                }
            });
        }
        $features = MembershipFeatures::displayFeatures();

        $memberships->each(function ($membership) use ($features): void {
            [$enabledFeatures, $disabledFeatures] = collect($features)
                ->partition(fn ($value, $name) => in_array($name, $membership->getAttribute('features')))
                ->toArray();

            $membership->setAttribute('enableFeatures', $enabledFeatures);
            $membership->setAttribute('disableFeatures', $disabledFeatures);
        });

        return $memberships;
    }

    public function render(): View
    {
        return view('livewire.creditor.membership-settings.page')
            ->with('memberships', $memberships = $this->setUp())
            ->with('specialMembershipExists', $memberships->where('company_id', $this->user->company_id)->isNotEmpty())
            ->title(__('Membership Settings'));
    }
}
