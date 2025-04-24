<?php

declare(strict_types=1);

namespace Tests\Feature\PayTerms;

use App\Enums\CompanyStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\PayTerms\EditPage;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Group;
use App\Models\Subclient;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class EditPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $subclient = Subclient::factory()->create(['company_id' => $this->user->company_id]);

        $this->get(route('creditor.pay-terms.edit', [
            'id' => $subclient->id,
            'payTerms' => 'sub-account-terms',
        ]))
            ->assertSeeLivewire(EditPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_livewire_component_has_view_file(): void
    {
        $subclient = Subclient::factory()->create(['company_id' => $this->user->company_id]);

        Livewire::test(EditPage::class, [
            'id' => $subclient->id,
            'payTerms' => 'sub-account-terms',
        ])
            ->assertViewIs('livewire.creditor.pay-terms.edit-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_un_authorized(): void
    {
        CompanyMembership::factory()->create(['company_id' => $this->user->company_id]);

        $this->user->company()->update(['status' => CompanyStatus::ACTIVE]);

        $subclient = Subclient::factory()->create(['company_id' => $this->user->company_id]);

        $this->get(route('creditor.pay-terms.edit', ['id' => $subclient->id, 'payTerms' => 'Test']))
            ->assertDontSeeLivewire(EditPage::class)
            ->assertStatus(404);
    }

    #[Test]
    public function it_can_view_pay_terms_options_with_sub_client(): void
    {
        $this->user->assignRole(Role::query()->create(['name' => EnumRole::CREDITOR]));
        $this->user->update(['subclient_id' => null]);

        $subclient = Subclient::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => 30,
                'ppa_balance_discount_percent' => 30,
                'min_monthly_pay_percent' => 20,
                'max_days_first_pay' => 30,
                'minimum_settlement_percentage' => 25,
                'minimum_payment_plan_percentage' => 25,
                'max_first_pay_days' => 100,
            ]);

        Livewire::test(EditPage::class, [
            'id' => $subclient->id,
            'payTerms' => 'sub-account-terms',
        ])
            ->assertViewHas('payTermsOption', [
                'subclient_' . $subclient->id => $subclient->subclient_name . '/' . $subclient->unique_identification_number,
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_view_pay_terms_options_with_group(): void
    {
        $this->user->assignRole(Role::query()->create(['name' => EnumRole::CREDITOR]));
        $this->user->update(['subclient_id' => null]);

        $group = Group::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => 30,
                'ppa_balance_discount_percent' => 30,
                'min_monthly_pay_percent' => 20,
                'max_days_first_pay' => 30,
                'minimum_settlement_percentage' => 25,
                'minimum_payment_plan_percentage' => 25,
                'max_first_pay_days' => 100,
            ]);

        Livewire::test(EditPage::class, [
            'id' => $group->id,
            'payTerms' => 'group-terms',
        ])
            ->assertViewHas('payTermsOption', [
                'group_' . $group->id => $group->name,
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_update_master_terms_pay_terms(): void
    {
        $this->user->update(['subclient_id' => null]);

        Livewire::test(EditPage::class, [
            'id' => $this->user->company_id,
            'payTerms' => 'master-terms',
        ])
            ->assertSet('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', $pif = 30)
            ->set('form.ppa_balance_discount_percent', $ppa = 30)
            ->set('form.min_monthly_pay_percent', $minAmount = 20)
            ->set('form.max_days_first_pay', $maxDays = 40)
            ->set('form.minimum_settlement_percentage', $minSettlementPercentage = 25)
            ->set('form.minimum_payment_plan_percentage', $minPaymentPercentage = 15)
            ->set('form.max_first_pay_days', $maxFirstPayDays = 100)
            ->call('update')
            ->assertRedirect(route('creditor.pay-terms'))
            ->assertOk();

        $this->assertDatabaseHas(Company::class, [
            'id' => $this->user->company_id,
            'pif_balance_discount_percent' => $pif,
            'ppa_balance_discount_percent' => $ppa,
            'min_monthly_pay_percent' => $minAmount,
            'max_days_first_pay' => $maxDays,
            'minimum_settlement_percentage' => $minSettlementPercentage,
            'minimum_payment_plan_percentage' => $minPaymentPercentage,
            'max_first_pay_days' => $maxFirstPayDays,
        ]);
    }

    #[Test]
    public function it_can_update_sub_client_pay_terms(): void
    {
        $this->user->update(['subclient_id' => null]);

        $subclient = Subclient::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => fake()->numberBetween(0, 99),
                'ppa_balance_discount_percent' => fake()->numberBetween(0, 99),
                'min_monthly_pay_percent' => fake()->numberBetween(1, 99),
                'max_days_first_pay' => fake()->numberBetween(1, 30),
                'minimum_settlement_percentage' => fake()->numberBetween(2, 30),
                'minimum_payment_plan_percentage' => fake()->numberBetween(2, 30),
                'max_first_pay_days' => fake()->numberBetween(100, 999),
            ]);

        Livewire::test(EditPage::class, [
            'id' => $subclient->id,
            'payTerms' => 'sub-account-terms',
        ])
            ->set('form.pay_terms', 'subclient_' . $subclient->id)
            ->set('form.pif_balance_discount_percent', $pif = 40)
            ->set('form.ppa_balance_discount_percent', $ppa = 40)
            ->set('form.min_monthly_pay_percent', $minAmount = 30)
            ->set('form.max_days_first_pay', $maxDays = 50)
            ->set('form.minimum_settlement_percentage', $minSettlementPercentage = 30)
            ->set('form.minimum_payment_plan_percentage', $minPaymentPercentage = 25)
            ->set('form.max_first_pay_days', $maxFirstPayDays = 60)
            ->call('update')
            ->assertRedirect(route('creditor.pay-terms'))
            ->assertOk();

        $this->assertDatabaseHas(Subclient::class, [
            'id' => $subclient->id,
            'pif_balance_discount_percent' => $pif,
            'ppa_balance_discount_percent' => $ppa,
            'min_monthly_pay_percent' => $minAmount,
            'max_days_first_pay' => $maxDays,
            'minimum_settlement_percentage' => $minSettlementPercentage,
            'minimum_payment_plan_percentage' => $minPaymentPercentage,
            'max_first_pay_days' => $maxFirstPayDays,
        ]);
    }

    #[Test]
    public function it_can_update_group_pay_terms(): void
    {
        $group = Group::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => fake()->numberBetween(0, 99),
                'ppa_balance_discount_percent' => fake()->numberBetween(0, 99),
                'min_monthly_pay_percent' => fake()->numberBetween(1, 99),
                'max_days_first_pay' => fake()->numberBetween(1, 30),
                'minimum_settlement_percentage' => fake()->numberBetween(2, 30),
                'minimum_payment_plan_percentage' => fake()->numberBetween(2, 30),
                'max_first_pay_days' => fake()->numberBetween(100, 999),
            ]);

        Livewire::test(EditPage::class, [
            'id' => $group->id,
            'payTerms' => 'group-terms',
        ])
            ->set('form.pay_terms', 'group_' . $group->id)
            ->set('form.pif_balance_discount_percent', $pif = 40)
            ->set('form.ppa_balance_discount_percent', $ppa = 40)
            ->set('form.min_monthly_pay_percent', $minAmount = 30)
            ->set('form.max_days_first_pay', $maxDays = 50)
            ->set('form.minimum_settlement_percentage', $minSettlementPercentage = 30)
            ->set('form.minimum_payment_plan_percentage', $minPaymentPercentage = 25)
            ->set('form.max_first_pay_days', $maxFirstPayDays = 60)
            ->call('update')
            ->assertRedirect(route('creditor.pay-terms'))
            ->assertOk();

        $this->assertDatabaseHas(Group::class, [
            'id' => $group->id,
            'pif_balance_discount_percent' => $pif,
            'ppa_balance_discount_percent' => $ppa,
            'min_monthly_pay_percent' => $minAmount,
            'max_days_first_pay' => $maxDays,
            'minimum_settlement_percentage' => $minSettlementPercentage,
            'minimum_payment_plan_percentage' => $minPaymentPercentage,
            'max_first_pay_days' => $maxFirstPayDays,
        ]);
    }

    #[Test]
    public function it_can_show_validation_error_when_min_settlement_grater_than_pif_balance_discount_percent(): void
    {
        $group = Group::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => fake()->numberBetween(0, 99),
                'ppa_balance_discount_percent' => fake()->numberBetween(0, 99),
                'min_monthly_pay_percent' => fake()->numberBetween(1, 99),
                'max_days_first_pay' => fake()->numberBetween(1, 30),
                'minimum_settlement_percentage' => fake()->numberBetween(2, 30),
                'minimum_payment_plan_percentage' => fake()->numberBetween(2, 30),
                'max_first_pay_days' => fake()->numberBetween(100, 999),
            ]);

        Livewire::test(EditPage::class, [
            'id' => $group->id,
            'payTerms' => 'group-terms',
        ])
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', 30)
            ->set('form.ppa_balance_discount_percent', 30)
            ->set('form.min_monthly_pay_percent', 20)
            ->set('form.max_days_first_pay', 40)
            ->set('form.minimum_settlement_percentage', 31)
            ->set('form.minimum_payment_plan_percentage', 10)
            ->set('form.max_first_pay_days', 100)
            ->call('update')
            ->assertHasErrors([
                'form.minimum_settlement_percentage' => ['lt:pif_balance_discount_percent'],
            ])
            ->assertHasNoErrors('form.pay_terms')
            ->assertOk();
    }

    #[Test]
    public function it_can_show_validation_error_when_min_payment_plan_grater_than_min_monthly_pay_percent(): void
    {
        $group = Group::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => fake()->numberBetween(0, 99),
                'ppa_balance_discount_percent' => fake()->numberBetween(0, 99),
                'min_monthly_pay_percent' => fake()->numberBetween(1, 99),
                'max_days_first_pay' => fake()->numberBetween(1, 30),
                'minimum_settlement_percentage' => fake()->numberBetween(2, 30),
                'minimum_payment_plan_percentage' => fake()->numberBetween(2, 30),
                'max_first_pay_days' => fake()->numberBetween(100, 999),
            ]);

        Livewire::test(EditPage::class, [
            'id' => $group->id,
            'payTerms' => 'group-terms',
        ])
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', 30)
            ->set('form.ppa_balance_discount_percent', 30)
            ->set('form.min_monthly_pay_percent', 20)
            ->set('form.max_days_first_pay', 40)
            ->set('form.minimum_settlement_percentage', 25)
            ->set('form.minimum_payment_plan_percentage', 21)
            ->set('form.max_first_pay_days', 100)
            ->call('update')
            ->assertHasErrors([
                'form.minimum_payment_plan_percentage' => ['lt:min_monthly_pay_percent'],
            ])
            ->assertHasNoErrors('form.pay_terms')
            ->assertOk();
    }

    #[Test]
    public function it_can_show_validation_error_max_first_pay_days_less_than_max_first_pay_days(): void
    {
        $group = Group::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => fake()->numberBetween(0, 99),
                'ppa_balance_discount_percent' => fake()->numberBetween(0, 99),
                'min_monthly_pay_percent' => fake()->numberBetween(1, 99),
                'max_days_first_pay' => fake()->numberBetween(1, 30),
                'minimum_settlement_percentage' => fake()->numberBetween(2, 30),
                'minimum_payment_plan_percentage' => fake()->numberBetween(2, 30),
                'max_first_pay_days' => fake()->numberBetween(100, 999),
            ]);

        Livewire::test(EditPage::class, [
            'id' => $group->id,
            'payTerms' => 'group-terms',
        ])
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', 30)
            ->set('form.ppa_balance_discount_percent', 30)
            ->set('form.min_monthly_pay_percent', 20)
            ->set('form.max_days_first_pay', 40)
            ->set('form.minimum_settlement_percentage', 25)
            ->set('form.minimum_payment_plan_percentage', 15)
            ->set('form.max_first_pay_days', 35)
            ->call('update')
            ->assertHasErrors([
                'form.max_first_pay_days' => ['gt:max_days_first_pay'],
            ])
            ->assertHasNoErrors('form.pay_terms')
            ->assertOk();
    }
}
