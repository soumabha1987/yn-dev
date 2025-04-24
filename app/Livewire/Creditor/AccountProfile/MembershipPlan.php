<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AccountProfile;

use App\Enums\CompanyMembershipStatus;
use App\Enums\MembershipFeatures;
use App\Enums\MembershipFrequency;
use App\Livewire\Creditor\Traits\Logout;
use App\Models\CompanyMembership;
use App\Models\Membership;
use App\Models\User;
use App\Services\CompanyMembershipService;
use App\Services\MembershipService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MembershipPlan extends Component
{
    use Logout;

    public int|string $selectedMembership = '';

    public bool $displaySuccessModal = false;

    protected MembershipService $membershipService;

    private User $user;

    public function __construct()
    {
        $this->membershipService = app(MembershipService::class);
        $this->user = Auth::user();

        $this->user->loadMissing('company');
    }

    public function mount(): void
    {
        $this->selectedMembership = app(CompanyMembershipService::class)
            ->findInActiveByCompany($this->user->company_id)
            ->membership->id ?? '';
    }

    public function purchaseMembership(int $membershipId = 0): void
    {
        $this->selectedMembership = $membershipId === 0 ? $this->selectedMembership : $membershipId;

        $validatedData = $this->validate([
            'selectedMembership' => ['required', Rule::exists(Membership::class, 'id')->where('status', true)],
        ]);

        $membership = $this->membershipService->findById($validatedData['selectedMembership']);

        $endPlanDate = match ($membership->frequency) {
            MembershipFrequency::WEEKLY => now()->addWeek(),
            MembershipFrequency::MONTHLY => now()->addMonthNoOverflow(),
            MembershipFrequency::YEARLY => now()->addYear(),
        };

        CompanyMembership::query()->updateOrCreate(
            ['company_id' => $this->user->company_id],
            [
                'membership_id' => $membership->id,
                'status' => CompanyMemberShipStatus::INACTIVE,
                'current_plan_start' => now(),
                'current_plan_end' => $endPlanDate,
            ]
        );

        $this->dispatch('next')->to(IndexPage::class);
    }

    public function render(): View
    {
        $memberships = $this->membershipService->fetchEnabled($this->user->company_id);

        $memberships->each(function (Membership $membership): void {
            [$enabledFeatures, $disabledFeatures] = collect(MembershipFeatures::displayFeatures())
                ->partition(fn ($value, $name) => in_array($name, $membership->getAttribute('features')));

            $membership->setAttribute('enableFeatures', $enabledFeatures);
            $membership->setAttribute('disableFeatures', $disabledFeatures);
        });

        return view('livewire.creditor.account-profile.membership-plan')
            ->with('memberships', $memberships);
    }
}
