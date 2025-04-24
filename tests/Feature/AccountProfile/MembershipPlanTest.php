<?php

declare(strict_types=1);

namespace Tests\Feature\AccountProfile;

use App\Enums\CompanyMembershipStatus;
use App\Enums\MembershipFrequency;
use App\Livewire\Creditor\AccountProfile\MembershipPlan;
use App\Models\CompanyMembership;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MembershipPlanTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        Livewire::actingAs($this->user)
            ->test(MembershipPlan::class)
            ->assertViewHas('memberships', fn (Collection $memberships) => $memberships->isEmpty())
            ->assertViewIs('livewire.creditor.account-profile.membership-plan')
            ->assertSee(__('Enterprise'))
            ->assertSee(__('Custom plans for enterprises that need to scale'))
            ->assertSee(__('Premium support'))
            ->assertSee(__('Dedicated Consumer Success Manager'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_membership_data(): void
    {
        $membership = Membership::factory()->create([
            'status' => true,
            'price' => 100,
            'fee' => 50,
        ]);

        Livewire::actingAs($this->user)
            ->test(MembershipPlan::class)
            ->assertViewIs('livewire.creditor.account-profile.membership-plan')
            ->assertViewHas('memberships', fn (Collection $memberships) => $membership->is($memberships->first()))
            ->assertSee($membership->name)
            ->assertSee($membership->description)
            ->assertSee(Number::percentage($membership->fee, 2))
            ->assertSee(Number::currency((float) $membership->price))
            ->assertSee($membership->frequency->displayName())
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_exists_validation_error_while_store_the_records(): void
    {
        Livewire::actingAs($this->user)
            ->test(MembershipPlan::class)
            ->call('purchaseMembership', 1)
            ->assertStatus(200)
            ->assertHasErrors(['selectedMembership']);
    }

    #[Test]
    public function it_can_purchase_membership_but_payment_not_cut_yet(): void
    {
        $this->travelTo(now()->addYears(2));

        $membership = Membership::factory()->create(['status' => true]);

        Livewire::actingAs($this->user)
            ->test(MembershipPlan::class)
            ->call('purchaseMembership', $membership->id)
            ->assertHasNoErrors()
            ->assertOk();

        $endPlanDate = match ($membership->frequency) {
            MembershipFrequency::WEEKLY => now()->addWeek(),
            MembershipFrequency::MONTHLY => now()->addMonthNoOverflow(),
            MembershipFrequency::YEARLY => now()->addYear(),
        };

        $this->assertDatabaseHas(CompanyMembership::class, [
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'current_plan_start' => now()->toDateTimeString(),
            'current_plan_end' => $endPlanDate,
            'status' => CompanyMembershipStatus::INACTIVE,
        ]);
    }
}
