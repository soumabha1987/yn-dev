<?php

declare(strict_types=1);

namespace Tests\Feature\AccountProfile;

use App\Enums\CompanyCategory;
use App\Enums\CompanyMembershipStatus;
use App\Enums\CompanyStatus;
use App\Enums\IndustryType;
use App\Enums\MembershipTransactionStatus;
use App\Enums\Role as EnumRole;
use App\Enums\State;
use App\Enums\Timezone;
use App\Livewire\Creditor\AccountProfile\IndexPage;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\MembershipTransaction;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IndexPageTest extends TestCase
{
    protected Company $company;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['status' => CompanyStatus::CREATED]);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    #[Test]
    public function it_can_render_account_profile_registration_step_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('creditor.profile'))
            ->assertSeeLivewire(IndexPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        $this->user->company()->update([
            'tilled_profile_completed_at' => null,
            'tilled_merchant_account_id' => null,
            'business_category' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(IndexPage::class)
            ->assertSet('currentStep', 'creditor.account-profile.company-profile')
            ->assertSet('steps', [
                'creditor.account-profile.company-profile',
                'creditor.account-profile.membership-plan',
                'creditor.account-profile.billing-details',
            ])
            ->assertSet('completedSteps', [])
            ->assertViewIs('livewire.creditor.account-profile.index-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_redirect_on_account_settings_page_once_we_already_registered(): void
    {
        $companyMembership = CompanyMembership::factory()
            ->for($this->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => now()->addMonth(),
            ]);

        MembershipTransaction::factory()
            ->for($this->company)
            ->create([
                'membership_id' => $companyMembership->membership_id,
                'status' => MembershipTransactionStatus::SUCCESS,
            ]);

        $this->company->update(['status' => fake()->randomElement([CompanyStatus::ACTIVE, CompanyStatus::SUBMITTED])]);

        Livewire::actingAs($this->user)
            ->test(IndexPage::class)
            ->assertRedirect(route('creditor.settings'));
    }

    #[Test]
    public function it_can_check_all_steps_are_completed(): void
    {
        $this->company->update([
            'company_name' => fake()->company(),
            'owner_full_name' => fake()->name(),
            'owner_email' => fake()->email(),
            'industry_type' => fake()->randomElement(IndustryType::values()),
            'company_category' => fake()->randomElement(CompanyCategory::values()),
            'fed_tax_id' => fake()->randomNumber(2),
            'owner_phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(State::values()),
            'zip' => fake()->numberBetween(1, 2000),
            'timezone' => fake()->randomElement((Timezone::values())),
            'url' => 'https://test.test',
            'from_time' => fake()->time('H:i'),
            'to_time' => fake()->time('H:i'),
            'from_day' => fake()->numberBetween('0', '6'),
            'to_day' => fake()->numberBetween('0', '6'),
        ]);

        CompanyMembership::factory()->create([
            'company_id' => $this->company->id,
            'status' => CompanyMembershipStatus::INACTIVE,
        ]);

        Livewire::actingAs($this->user)
            ->test(IndexPage::class)
            ->assertSet('currentStep', 'creditor.account-profile.billing-details')
            ->assertSet('steps', [
                'creditor.account-profile.company-profile',
                'creditor.account-profile.membership-plan',
                'creditor.account-profile.billing-details',
            ])
            ->assertSet('completedSteps', [
                'creditor.account-profile.company-profile',
                'creditor.account-profile.membership-plan',
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_go_to_next_step(): void
    {
        $this->company->update([
            'tilled_profile_completed_at' => null,
            'tilled_merchant_account_id' => null,
            'business_category' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(IndexPage::class)
            ->assertSet('currentStep', 'creditor.account-profile.company-profile')
            ->dispatch('next')
            ->assertSet('currentStep', 'creditor.account-profile.membership-plan')
            ->assertOk();
    }

    #[Test]
    public function it_can_go_to_previous_step(): void
    {
        $this->company->update([
            'company_name' => fake()->company(),
            'owner_full_name' => fake()->name(),
            'owner_email' => fake()->email(),
            'industry_type' => fake()->randomElement(IndustryType::values()),
            'company_category' => fake()->randomElement(CompanyCategory::values()),
            'fed_tax_id' => fake()->randomNumber(2),
            'owner_phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(State::values()),
            'zip' => fake()->numberBetween(1, 2000),
            'tilled_profile_completed_at' => null,
            'tilled_merchant_account_id' => null,
            'timezone' => fake()->randomElement((Timezone::values())),
            'url' => 'https://test.test',
            'from_time' => fake()->time('H:i'),
            'to_time' => fake()->time('H:i'),
            'from_day' => fake()->numberBetween('0', '6'),
            'to_day' => fake()->numberBetween('0', '6'),
        ]);

        Livewire::actingAs($this->user)
            ->test(IndexPage::class)
            ->assertSet('currentStep', 'creditor.account-profile.membership-plan')
            ->dispatch('previous')
            ->assertSet('currentStep', 'creditor.account-profile.company-profile')
            ->assertOk();
    }

    #[Test]
    public function it_can_set_the_card_title_of_the_current_step(): void
    {
        Livewire::actingAs($this->user)
            ->test(IndexPage::class)
            ->call('cardTitle', 'creditor.account-profile.company-profile')
            ->assertSee(__('Company Profile'))
            ->assertOk();
    }

    #[Test]
    public function it_can_set_switch_steps_using_index(): void
    {
        CompanyMembership::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => CompanyMembershipStatus::INACTIVE,
        ]);

        Livewire::actingAs($this->user)
            ->test(IndexPage::class)
            ->call('switchStep', fake()->numberBetween(6))
            ->assertSet('currentStep', 'creditor.account-profile.billing-details')
            ->call('switchStep', 1)
            ->assertSet('currentStep', 'creditor.account-profile.membership-plan')
            ->assertOk();
    }
}
