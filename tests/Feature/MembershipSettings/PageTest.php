<?php

declare(strict_types=1);

namespace Tests\Feature\MembershipSettings;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\MembershipFrequency;
use App\Enums\MembershipTransactionStatus;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\MembershipInquiries\Card;
use App\Livewire\Creditor\MembershipSettings\Page;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Membership;
use App\Models\MembershipPaymentProfile;
use App\Models\MembershipTransaction;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PageTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_will_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->user->company()->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $membership = Membership::factory()->create(['status' => true]);
        CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
        ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('creditor.membership-settings'))
            ->assertSeeLivewire(Page::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        $membership = Membership::factory()->create(['status' => true]);

        CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'next_membership_plan_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertViewIs('livewire.creditor.membership-settings.page')
            ->assertViewHas('memberships', fn (Collection $memberships) => $memberships->isNotEmpty())
            ->assertViewHas('specialMembershipExists', false)
            ->assertSeeLivewire(Card::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_special_membership(): void
    {
        $membership = Membership::factory()->create(['status' => false]);

        CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'next_membership_plan_id' => null,
        ]);

        [$specialMembership, $otherCompanySpecialMembership] = Membership::factory()
            ->forEachSequence(
                ['company_id' => $this->user->company_id],
                ['company_id' => Company::factory()],
            )
            ->create(['status' => true]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertViewIs('livewire.creditor.membership-settings.page')
            ->assertViewHas('memberships', function (Collection $memberships) use ($specialMembership, $otherCompanySpecialMembership, $membership): bool {
                return $memberships->isNotEmpty()
                    && $memberships->doesntContain($otherCompanySpecialMembership)
                    && $memberships->whereIn('id', [$specialMembership->id, $membership->id]);
            })
            ->assertViewHas('specialMembershipExists', true)
            ->assertDontSeeLivewire(Card::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_set_the_attribute(): void
    {
        $membership = Membership::factory()->create([
            'frequency' => MembershipFrequency::WEEKLY,
            'price' => 28,
            'status' => true,
        ]);

        CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'next_membership_plan_id' => null,
            'auto_renew' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertViewIs('livewire.creditor.membership-settings.page')
            ->assertViewHas(
                'memberships',
                fn (Collection $memberships) => Number::format((float) $memberships->first()->price_per_day, 2) === '4.00'
            );
    }

    #[Test]
    public function it_can_call_undo_cancelled(): void
    {
        $membership = Membership::factory()->create([
            'frequency' => MembershipFrequency::WEEKLY,
            'price' => 28,
            'status' => true,
        ]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'next_membership_plan_id' => null,
            'auto_renew' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->call('undoCancelled')
            ->assertOk();

        Notification::assertNotified(__('We have successfully removed your cancellation request!'));

        $this->assertTrue($companyMembership->refresh()->auto_renew);
    }

    #[Test]
    public function it_can_call_already_active_plan_undo_cancelled(): void
    {
        $membership = Membership::factory()->create([
            'frequency' => MembershipFrequency::WEEKLY,
            'price' => 28,
            'status' => true,
        ]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'next_membership_plan_id' => null,
            'auto_renew' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->call('undoCancelled')
            ->assertOk();

        Notification::assertNotNotified(__('Your plan\'s auto-renewal has been successfully activated.'));

        $this->assertTrue($companyMembership->refresh()->auto_renew);
    }

    #[Test]
    public function it_can_call_cancel_auto_renew_plan_with_remove_profile(): void
    {
        $membership = Membership::factory()->create([
            'frequency' => MembershipFrequency::WEEKLY,
            'price' => 28,
            'status' => true,
        ]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'next_membership_plan_id' => null,
            'auto_renew' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertSet('cancelledPlanRemoveProfile', false)
            ->assertSet('cancelledPlanKeepProfile', false)
            ->call('cancelAutoRenewPlan', 'true')
            ->assertOk()
            ->assertSet('cancelledPlanRemoveProfile', true)
            ->assertSet('cancelledPlanKeepProfile', false);

        $this->assertFalse($companyMembership->refresh()->auto_renew);

        $this->assertEquals(1, $this->user->company->refresh()->remove_profile);
    }

    #[Test]
    public function it_can_call_cancel_auto_renew_plan_without_remove_profile(): void
    {
        $membership = Membership::factory()->create([
            'frequency' => MembershipFrequency::WEEKLY,
            'price' => 28,
            'status' => true,
        ]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'next_membership_plan_id' => null,
            'auto_renew' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertSet('cancelledPlanRemoveProfile', false)
            ->assertSet('cancelledPlanKeepProfile', false)
            ->call('cancelAutoRenewPlan')
            ->assertOk()
            ->assertSet('cancelledPlanRemoveProfile', false)
            ->assertSet('cancelledPlanKeepProfile', true);

        $this->assertFalse($companyMembership->refresh()->auto_renew);

        $this->assertEquals(0, $this->user->company->refresh()->remove_profile);
    }

    #[Test]
    public function it_can_call_submit_cancelled_note(): void
    {
        $membership = Membership::factory()->create([
            'frequency' => MembershipFrequency::WEEKLY,
            'price' => 28,
            'status' => true,
        ]);

        CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'next_membership_plan_id' => null,
            'auto_renew' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->set('cancelled_note', $note = fake()->text())
            ->call('submitCancelledNote')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('cancelledPlanRemoveProfile', false)
            ->assertSet('cancelledPlanKeepProfile', false);

        $this->assertEquals($note, $this->user->company->refresh()->cancelled_note);
    }

    #[Test]
    public function it_can_call_submit_cancelled_note_max_validation(): void
    {
        $membership = Membership::factory()->create([
            'frequency' => MembershipFrequency::WEEKLY,
            'price' => 28,
            'status' => true,
        ]);

        CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'next_membership_plan_id' => null,
            'auto_renew' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->set('cancelled_note', $note = str('A')->repeat(251))
            ->call('submitCancelledNote')
            ->assertOk()
            ->assertHasErrors(['cancelled_note' => ['max:250']]);

        $this->assertNotEquals($note, $this->user->company->refresh()->cancelled_note);
    }

    #[Test]
    public function it_can_remove_next_plan(): void
    {
        $membership = Membership::factory()->create(['status' => true]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
        ]);

        $this->assertNotNull($companyMembership->next_membership_plan_id);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->call('nextPlanUpdate')
            ->assertOk();

        $this->assertNull($companyMembership->refresh()->next_membership_plan_id);
    }

    #[Test]
    public function active_plan(): void
    {
        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        MembershipPaymentProfile::factory()->create(['company_id' => $this->user->company_id]);

        Http::fake(fn () => Http::response(['status' => fake()->randomElement(['processing', 'succeeded'])]));

        $membership = Membership::factory()->create(['status' => true]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'auto_renew' => false,
        ]);

        $membership = Membership::factory()->create(['frequency' => MembershipFrequency::MONTHLY]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->call('activePlan', $membership)
            ->assertOk();

        $this->assertNull($companyMembership->refresh()->next_membership_plan_id);
        $this->assertEquals($membership->id, $companyMembership->membership_id);
        $this->assertEquals(CompanyMembershipStatus::ACTIVE, $companyMembership->status);
        $this->assertTrue($companyMembership->auto_renew);
        $this->assertEquals(now()->toDateString(), $companyMembership->current_plan_start->toDateString());
        $this->assertEquals(now()->addMonthNoOverflow()->toDateString(), $companyMembership->current_plan_end->toDateString());
    }

    #[Test]
    public function it_can_render_reprocess_failed_current_plan_transaction(): void
    {
        $this->travelTo(now()->addDay());

        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        MembershipPaymentProfile::factory()->create(['company_id' => $this->user->company_id]);

        Http::fake(fn () => Http::response(['status' => fake()->randomElement(['processing', 'succeeded'])]));

        $membership = Membership::factory()
            ->create([
                'price' => 100,
                'status' => true,
                'frequency' => MembershipFrequency::MONTHLY,
            ]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'current_plan_end' => now()->subDay(),
            'auto_renew' => true,
        ]);

        MembershipTransaction::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'status' => MembershipTransactionStatus::FAILED,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertSet('isLastTransactionFailed', true)
            ->assertSee(__('Your membership is inactive due to a failed payment. Click here to reprocess your payment'))
            ->call('activePlan', $membership)
            ->assertOk()
            ->assertSet('isLastTransactionFailed', false);

        $this->assertNull($companyMembership->refresh()->next_membership_plan_id);
        $this->assertEquals($membership->id, $companyMembership->membership_id);
        $this->assertEquals(CompanyMembershipStatus::ACTIVE, $companyMembership->status);
        $this->assertTrue($companyMembership->auto_renew);
        $this->assertTrue(Carbon::today()->equalTo($companyMembership->current_plan_start->toDateString()));
        $this->assertTrue(Carbon::today()->addMonthNoOverflow()->equalTo($companyMembership->current_plan_end->toDateString()));

        $this->assertDatabaseHas(MembershipTransaction::class, [
            'company_id' => $this->user->company_id,
            'membership_id' => $membership->id,
            'status' => MembershipTransactionStatus::SUCCESS,
            'price' => 100,
            'plan_end_date' => now()->addMonthNoOverflow()->toDateTimeString(),
        ]);
    }

    #[Test]
    public function it_can_render_upgrade_plan_with_membership_transaction(): void
    {
        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        MembershipPaymentProfile::factory()->create(['company_id' => $this->user->company_id]);

        Http::fake(fn () => Http::response(['status' => fake()->randomElement(['processing', 'succeeded'])]));

        $membership = Membership::factory()->create([
            'status' => true,
            'frequency' => MembershipFrequency::WEEKLY,
            'price' => 50,
        ]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'auto_renew' => true,
            'current_plan_end' => $endPlanDate = today()->addDays(4),
        ]);

        $newMembership = Membership::factory()->create([
            'status' => true,
            'frequency' => MembershipFrequency::MONTHLY,
            'price' => 1000,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->call('updateMembership', $newMembership)
            ->assertOk();

        $this->assertTrue($companyMembership->refresh()->auto_renew);

        $this->assertTrue(
            MembershipTransaction::query()
                ->where([
                    'company_id' => $this->user->company_id,
                    'membership_id' => $newMembership->id,
                    'status' => MembershipTransactionStatus::SUCCESS,
                ])
                ->where('plan_end_date', $endPlanDate)
                ->exists()
        );

        $this->assertNull($companyMembership->next_membership_plan_id);
        $this->assertEquals($newMembership->id, $companyMembership->membership_id);
        $this->assertTrue($endPlanDate->equalTo($companyMembership->current_plan_end));
    }

    #[Test]
    public function it_can_render_expired_plan_with_auto_renew_false(): void
    {
        $membership = Membership::factory()
            ->create([
                'status' => true,
                'frequency' => MembershipFrequency::MONTHLY,
            ]);

        CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'current_plan_end' => now()->subDay(),
            'next_membership_plan_id' => null,
            'auto_renew' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertSet('isLastTransactionFailed', false)
            ->assertSee(__('Your membership is expired. Please choose your plan and start membership with YouNegotiate.'))
            ->assertOk();
    }

    #[Test]
    public function can_not_active_plan(): void
    {
        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        Http::fake(fn () => Http::response(['status' => 'failed']));

        $membership = Membership::factory()->create(['status' => true]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->call('activePlan', $membership)
            ->assertOk();

        $this->assertNotNull($companyMembership->refresh()->next_membership_plan_id);
    }

    #[Test]
    public function it_can_update_current_membership_but_it_will_choose_the_same(): void
    {
        $membership = Membership::factory()->create([
            'status' => true,
            'frequency' => MembershipFrequency::YEARLY,
            'price' => 10,
        ]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'auto_renew' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->call('updateMembership', $membership)
            ->assertOk();

        $this->assertTrue($companyMembership->refresh()->auto_renew);
        $this->assertEquals($companyMembership->next_membership_plan_id, $membership->id);
    }

    #[Test]
    public function downgrade_plan(): void
    {
        $membership = Membership::factory()->create([
            'status' => true,
            'frequency' => MembershipFrequency::WEEKLY,
            'price' => 500,
        ]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'auto_renew' => false,
        ]);

        $membership = Membership::factory()->create([
            'status' => true,
            'frequency' => MembershipFrequency::MONTHLY,
            'price' => 500,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->call('updateMembership', $membership)
            ->assertOk();

        $this->assertTrue($companyMembership->refresh()->auto_renew);
        $this->assertEquals($companyMembership->next_membership_plan_id, $membership->id);
    }

    #[Test]
    public function upgrade_plan(): void
    {
        $membership = Membership::factory()->create([
            'status' => true,
            'frequency' => MembershipFrequency::MONTHLY,
            'price' => 500,
        ]);

        $companyMembership = CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'auto_renew' => false,
            'current_plan_end' => now(),
        ]);

        $membership = Membership::factory()->create([
            'status' => true,
            'frequency' => MembershipFrequency::WEEKLY,
            'price' => 1000,
        ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->call('updateMembership', $membership)
            ->assertOk();

        $this->assertTrue($companyMembership->refresh()->auto_renew);
        $this->assertNull($companyMembership->next_membership_plan_id);
        $this->assertEquals($membership->id, $companyMembership->membership_id);
        $this->assertEquals(today()->format('M d, Y'), $companyMembership->current_plan_end->format('M d, Y'));
    }
}
