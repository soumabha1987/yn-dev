<?php

declare(strict_types=1);

namespace Tests\Feature\PayTerms;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerFields;
use App\Enums\CreditorCurrentStep;
use App\Enums\CustomContentType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\PayTerms\CreatePage;
use App\Models\Company;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\Group;
use App\Models\Merchant;
use App\Models\Subclient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class CreatePageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->get(route('creditor.pay-terms.create'))
            ->assertSeeLivewire(CreatePage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_livewire_component_has_view_file(): void
    {
        Livewire::test(CreatePage::class)
            ->assertViewIs('livewire.creditor.pay-terms.create-page')
            ->assertSee('master terms (minimum requirement)')
            ->assertOk();
    }

    #[Test]
    public function it_can_required_fields_validation(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', '')
            ->set('form.ppa_balance_discount_percent', '')
            ->set('form.min_monthly_pay_percent', '')
            ->set('form.max_days_first_pay', '')
            ->set('form.minimum_settlement_percentage', '')
            ->set('form.minimum_payment_plan_percentage', '')
            ->set('form.max_first_pay_days', '')
            ->call('save')
            ->assertHasErrors([
                'form.pif_balance_discount_percent' => ['required'],
                'form.ppa_balance_discount_percent' => ['required'],
                'form.min_monthly_pay_percent' => ['required'],
                'form.max_days_first_pay' => ['required'],
                'form.minimum_settlement_percentage' => ['required'],
                'form.minimum_payment_plan_percentage' => ['required'],
                'form.max_first_pay_days' => ['required'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_other_non_required_numeric_and_rule_in_validation(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', '')
            ->set('form.pif_balance_discount_percent', fake()->name)
            ->set('form.ppa_balance_discount_percent', fake()->name)
            ->set('form.min_monthly_pay_percent', fake()->name)
            ->set('form.max_days_first_pay', fake()->name)
            ->set('form.minimum_settlement_percentage', fake()->name)
            ->set('form.minimum_payment_plan_percentage', fake()->name)
            ->set('form.max_first_pay_days', fake()->name)
            ->call('save')
            ->assertHasErrors([
                'form.pay_terms' => ['required'],
                'form.pif_balance_discount_percent' => ['regex'],
                'form.ppa_balance_discount_percent' => ['regex'],
                'form.min_monthly_pay_percent' => ['regex'],
                'form.max_days_first_pay' => ['integer'],
                'form.minimum_settlement_percentage' => ['integer'],
                'form.minimum_payment_plan_percentage' => ['integer'],
                'form.max_first_pay_days' => ['integer'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_other_non_required_numeric_limit_master_terms_validation(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', fake()->numberBetween(101, 200))
            ->set('form.ppa_balance_discount_percent', fake()->numberBetween(101, 200))
            ->set('form.min_monthly_pay_percent', fake()->numberBetween(101, 200))
            ->set('form.max_days_first_pay', fake()->numberBetween(1001, 2000))
            ->set('form.minimum_settlement_percentage', fake()->numberBetween(101, 2000))
            ->set('form.minimum_payment_plan_percentage', fake()->numberBetween(101, 2000))
            ->set('form.max_first_pay_days', fake()->numberBetween(1001, 2000))
            ->call('save')
            ->assertHasErrors([
                'form.pif_balance_discount_percent' => ['max:100'],
                'form.ppa_balance_discount_percent' => ['max:100'],
                'form.min_monthly_pay_percent' => ['max:100'],
                'form.max_days_first_pay' => ['max:1000'],
                'form.minimum_settlement_percentage' => ['max:100'],
                'form.minimum_payment_plan_percentage' => ['max:100'],
                'form.max_first_pay_days' => ['max:1000'],
            ])
            ->assertHasNoErrors('form.pay_terms')
            ->assertOk();
    }

    #[Test]
    public function it_can_show_validation_error_when_min_settlement_grater_than_pif_balance_discount_percent(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', 30)
            ->set('form.ppa_balance_discount_percent', 30)
            ->set('form.min_monthly_pay_percent', 20)
            ->set('form.max_days_first_pay', 40)
            ->set('form.minimum_settlement_percentage', 31)
            ->set('form.minimum_payment_plan_percentage', 10)
            ->set('form.max_first_pay_days', 100)
            ->call('save')
            ->assertHasErrors([
                'form.minimum_settlement_percentage' => ['lt:pif_balance_discount_percent'],
            ])
            ->assertHasNoErrors('form.pay_terms')
            ->assertOk();
    }

    #[Test]
    public function it_can_show_validation_error_when_min_payment_plan_grater_than_min_monthly_pay_percent(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', 30)
            ->set('form.ppa_balance_discount_percent', 30)
            ->set('form.min_monthly_pay_percent', 20)
            ->set('form.max_days_first_pay', 40)
            ->set('form.minimum_settlement_percentage', 25)
            ->set('form.minimum_payment_plan_percentage', 21)
            ->set('form.max_first_pay_days', 100)
            ->call('save')
            ->assertHasErrors([
                'form.minimum_payment_plan_percentage' => ['lt:min_monthly_pay_percent'],
            ])
            ->assertHasNoErrors('form.pay_terms')
            ->assertOk();
    }

    #[Test]
    public function it_can_show_validation_error_max_first_pay_days_less_than_max_first_pay_days(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', 30)
            ->set('form.ppa_balance_discount_percent', 30)
            ->set('form.min_monthly_pay_percent', 20)
            ->set('form.max_days_first_pay', 40)
            ->set('form.minimum_settlement_percentage', 25)
            ->set('form.minimum_payment_plan_percentage', 15)
            ->set('form.max_first_pay_days', 35)
            ->call('save')
            ->assertHasErrors([
                'form.max_first_pay_days' => ['gt:max_days_first_pay'],
            ])
            ->assertHasNoErrors('form.pay_terms')
            ->assertOk();
    }

    #[Test]
    public function it_can_create_master_terms_when_setup_wizard_completed(): void
    {
        $this->user->update(['subclient_id' => null]);

        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 0);

        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', 20)
            ->set('form.ppa_balance_discount_percent', 10)
            ->set('form.min_monthly_pay_percent', 30)
            ->set('form.max_days_first_pay', 5)
            ->set('form.minimum_settlement_percentage', 5)
            ->set('form.minimum_payment_plan_percentage', 5)
            ->set('form.max_first_pay_days', 100)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('creditor.pay-terms'))
            ->assertOk();

        $this->assertDatabaseHas(Company::class, [
            'id' => $this->user->company_id,
            'pif_balance_discount_percent' => 20,
            'ppa_balance_discount_percent' => 10,
            'min_monthly_pay_percent' => 30,
            'max_days_first_pay' => 5,
            'minimum_settlement_percentage' => 5,
            'minimum_payment_plan_percentage' => 5,
            'max_first_pay_days' => 100,
        ]);
    }

    #[Test]
    public function it_can_create_master_terms_when_setup_wizard_in_completed(): void
    {
        $this->user->update(['subclient_id' => null]);

        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 1);

        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', 20)
            ->set('form.ppa_balance_discount_percent', 10)
            ->set('form.min_monthly_pay_percent', 30)
            ->set('form.max_days_first_pay', 5)
            ->set('form.minimum_settlement_percentage', 5)
            ->set('form.minimum_payment_plan_percentage', 5)
            ->set('form.max_first_pay_days', 100)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'))
            ->assertOk();

        $this->assertDatabaseHas(Company::class, [
            'id' => $this->user->company_id,
            'pif_balance_discount_percent' => 20,
            'ppa_balance_discount_percent' => 10,
            'min_monthly_pay_percent' => 30,
            'max_days_first_pay' => 5,
            'minimum_settlement_percentage' => 5,
            'minimum_payment_plan_percentage' => 5,
            'max_first_pay_days' => 100,
        ]);
    }

    #[Test]
    public function it_cannot_redirect_after_create_pay_terms_when_subclient_is_exist_and_setup_wizard_incompleted(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 0);

        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', 20)
            ->set('form.ppa_balance_discount_percent', 10)
            ->set('form.min_monthly_pay_percent', 30)
            ->set('form.max_days_first_pay', 5)
            ->set('form.minimum_settlement_percentage', 5)
            ->set('form.minimum_payment_plan_percentage', 5)
            ->set('form.max_first_pay_days', 100)
            ->call('save')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseHas(Company::class, [
            'id' => $this->user->company_id,
            'pif_balance_discount_percent' => 20,
            'ppa_balance_discount_percent' => 10,
            'min_monthly_pay_percent' => 30,
            'max_days_first_pay' => 5,
            'minimum_settlement_percentage' => 5,
            'minimum_payment_plan_percentage' => 5,
            'max_first_pay_days' => 100,
        ]);
    }

    #[Test]
    public function it_can_view_pay_terms_options_with_sub_client_and_group(): void
    {
        $this->user->assignRole(Role::query()->create(['name' => EnumRole::CREDITOR]));
        $this->user->update(['subclient_id' => null]);

        $subclient = Subclient::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => null,
                'ppa_balance_discount_percent' => null,
                'min_monthly_pay_percent' => null,
                'max_days_first_pay' => null,
                'minimum_settlement_percentage' => null,
                'minimum_payment_plan_percentage' => null,
                'max_first_pay_days' => null,
            ]);

        $group = Group::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => null,
                'ppa_balance_discount_percent' => null,
                'min_monthly_pay_percent' => null,
                'max_days_first_pay' => null,
                'minimum_settlement_percentage' => null,
                'minimum_payment_plan_percentage' => null,
                'max_first_pay_days' => null,
            ]);

        Livewire::test(CreatePage::class)
            ->assertViewHas('payTermsOption', function (Collection $payTermsOption) use ($subclient, $group): bool {
                return $payTermsOption->all() === [
                    'master_terms' => 'master terms (minimum requirement)',
                    'group_' . $group->id => 'group - ' . $group->name,
                    'subclient_' . $subclient->id => 'subclient - ' . $subclient->subclient_name . '/' . $subclient->unique_identification_number,
                ];
            })
            ->assertOk();
    }

    #[Test]
    public function it_can_create_sub_client_terms(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 0);

        $this->user->update(['subclient_id' => null]);

        $subclient = Subclient::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => null,
                'ppa_balance_discount_percent' => null,
                'min_monthly_pay_percent' => null,
                'max_days_first_pay' => null,
                'minimum_settlement_percentage' => null,
                'minimum_payment_plan_percentage' => null,
                'max_first_pay_days' => null,
            ]);

        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'subclient_' . $subclient->id)
            ->set('form.pif_balance_discount_percent', $pif = 20)
            ->set('form.ppa_balance_discount_percent', $ppa = 20)
            ->set('form.min_monthly_pay_percent', $minAmount = 10)
            ->set('form.max_days_first_pay', $maxDays = 30)
            ->set('form.minimum_settlement_percentage', $minSettlementPercentage = 10)
            ->set('form.minimum_payment_plan_percentage', $minPaymentPlanPercentage = 9)
            ->set('form.max_first_pay_days', $maxFirstPayDays = 100)
            ->call('save')
            ->assertRedirect(route('creditor.pay-terms'))
            ->assertOk();

        $this->assertDatabaseHas(Subclient::class, [
            'id' => $subclient->id,
            'pif_balance_discount_percent' => $pif,
            'ppa_balance_discount_percent' => $ppa,
            'min_monthly_pay_percent' => $minAmount,
            'max_days_first_pay' => $maxDays,
            'minimum_settlement_percentage' => $minSettlementPercentage,
            'minimum_payment_plan_percentage' => $minPaymentPlanPercentage,
            'max_first_pay_days' => $maxFirstPayDays,
        ]);
    }

    #[Test]
    public function it_can_create_group_terms(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 0);

        $this->user->update(['subclient_id' => null]);

        $group = Group::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_balance_discount_percent' => null,
                'ppa_balance_discount_percent' => null,
                'min_monthly_pay_percent' => null,
                'max_days_first_pay' => null,
                'minimum_settlement_percentage' => null,
                'minimum_payment_plan_percentage' => null,
                'max_first_pay_days' => null,
            ]);

        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'group_' . $group->id)
            ->set('form.pif_balance_discount_percent', $pif = 20)
            ->set('form.ppa_balance_discount_percent', $ppa = 20)
            ->set('form.min_monthly_pay_percent', $minAmount = 10)
            ->set('form.max_days_first_pay', $maxDays = 30)
            ->set('form.minimum_settlement_percentage', $minSettlementPercentage = 10)
            ->set('form.minimum_payment_plan_percentage', $minPaymentPlanPercentage = 9)
            ->set('form.max_first_pay_days', $maxFirstPayDays = 100)
            ->call('save')
            ->assertRedirect(route('creditor.pay-terms'))
            ->assertOk();

        $this->assertDatabaseHas(Group::class, [
            'id' => $group->id,
            'pif_balance_discount_percent' => $pif,
            'ppa_balance_discount_percent' => $ppa,
            'min_monthly_pay_percent' => $minAmount,
            'max_days_first_pay' => $maxDays,
            'minimum_settlement_percentage' => $minSettlementPercentage,
            'minimum_payment_plan_percentage' => $minPaymentPlanPercentage,
            'max_first_pay_days' => $maxFirstPayDays,
        ]);
    }

    #[Test]
    public function it_can_create_last_remain_wizard_setup(): void
    {
        $this->user->update(['subclient_id' => null]);

        $this->user->company()->update([
            'status' => CompanyStatus::ACTIVE,
            'pif_balance_discount_percent' => null,
            'ppa_balance_discount_percent' => null,
            'min_monthly_pay_percent' => null,
            'max_days_first_pay' => null,
            'minimum_settlement_percentage' => null,
            'minimum_payment_plan_percentage' => null,
            'max_first_pay_days' => null,
        ]);

        CustomContent::factory()
            ->forEachSequence(
                ['type' => CustomContentType::ABOUT_US],
                ['type' => CustomContentType::TERMS_AND_CONDITIONS]
            )
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
            ]);

        Merchant::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        CsvHeader::query()->create([
            'name' => fake()->word(),
            'subclient_id' => null,
            'company_id' => $this->user->company_id,
            'is_mapped' => true,
            'headers' => [
                'EMAIL_ID' => ConsumerFields::CONSUMER_EMAIL->value,
            ],
        ]);

        Livewire::test(CreatePage::class)
            ->set('form.pay_terms', 'master_terms')
            ->set('form.pif_balance_discount_percent', $pif = 20)
            ->set('form.ppa_balance_discount_percent', $ppa = 20)
            ->set('form.min_monthly_pay_percent', $minAmount = 10)
            ->set('form.max_days_first_pay', $maxDays = 30)
            ->set('form.minimum_settlement_percentage', $minSettlementPercentage = 10)
            ->set('form.minimum_payment_plan_percentage', $minPaymentPlanPercentage = 9)
            ->set('form.max_first_pay_days', $maxFirstPayDays = 100)
            ->call('save')
            ->assertRedirect(route('home'))
            ->assertOk()
            ->assertSessionHas('show-wizard-completed-modal');

        $this->assertDatabaseHas(Company::class, [
            'id' => $this->user->company_id,
            'pif_balance_discount_percent' => $pif,
            'ppa_balance_discount_percent' => $ppa,
            'min_monthly_pay_percent' => $minAmount,
            'max_days_first_pay' => $maxDays,
            'minimum_settlement_percentage' => $minSettlementPercentage,
            'minimum_payment_plan_percentage' => $minPaymentPlanPercentage,
            'max_first_pay_days' => $maxFirstPayDays,
        ]);
    }
}
