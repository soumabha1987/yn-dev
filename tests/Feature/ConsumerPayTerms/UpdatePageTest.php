<?php

declare(strict_types=1);

namespace Tests\Feature\ConsumerPayTerms;

use AllowDynamicProperties;
use App\Enums\CommunicationCode;
use App\Enums\CompanyStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumsRole;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Creditor\ConsumerPayTerms\UpdatePage;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[AllowDynamicProperties]
class UpdatePageTest extends TestCase
{
    protected Consumer $consumer;

    protected Subclient $subclient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
        ]);

        $this->user = User::factory()->for($this->company)->create(['subclient_id' => null]);

        $role = Role::query()->create(['name' => EnumsRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->actingAs($this->user);

        $this->consumer = Consumer::factory()->for($this->company)->create();
    }

    #[Test]
    public function it_can_render_livewire_view_page(): void
    {
        Livewire::test(UpdatePage::class, ['record' => $this->consumer])
            ->assertViewIs('livewire.creditor.consumer-pay-terms.update-page')
            ->assertSet('form.pif_discount_percent', $this->consumer->pif_discount_percent)
            ->assertSet('form.pay_setup_discount_percent', $this->consumer->pay_setup_discount_percent)
            ->assertSet('form.min_monthly_pay_percent', $this->consumer->min_monthly_pay_percent)
            ->assertSet('form.max_days_first_pay', $this->consumer->max_days_first_pay)
            ->assertSet('form.minimum_settlement_percentage', $this->consumer->minimum_settlement_percentage)
            ->assertSet('form.minimum_payment_plan_percentage', $this->consumer->minimum_payment_plan_percentage)
            ->assertSet('form.max_first_pay_days', $this->consumer->max_first_pay_days)
            ->assertOk();
    }

    #[Test]
    #[DataProvider('validationRule')]
    public function it_can_render_validation_rule(array $requestData, array $requestErrors): void
    {
        Livewire::test(UpdatePage::class, ['record' => $this->consumer])
            ->set($requestData)
            ->call('update')
            ->assertOk()
            ->assertHasErrors($requestErrors);
    }

    #[Test]
    public function it_can_update_all_offer_fields_for_consumer(): void
    {
        Queue::fake();

        Livewire::test(UpdatePage::class, ['record' => $this->consumer])
            ->set([
                'form.pif_discount_percent' => $pif = 30,
                'form.pay_setup_discount_percent' => $ppa = 30,
                'form.min_monthly_pay_percent' => $minMonthly = 20,
                'form.max_days_first_pay' => $maxDays = 30,
                'form.minimum_settlement_percentage' => $minSettlementPercentage = 10,
                'form.minimum_payment_plan_percentage' => $minPaymentPlanPercentage = 10,
                'form.max_first_pay_days' => $maxFirstPayDays = 100,
            ])
            ->call('update')
            ->assertOk()
            ->assertHasNoErrors();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::UPDATE_PAY_TERMS_OFFER
        );

        $this->assertDatabaseHas(Consumer::class, [
            'id' => $this->consumer->id,
            'pif_discount_percent' => $pif,
            'pay_setup_discount_percent' => $ppa,
            'min_monthly_pay_percent' => $minMonthly,
            'max_days_first_pay' => $maxDays,
            'minimum_settlement_percentage' => $minSettlementPercentage,
            'minimum_payment_plan_percentage' => $minPaymentPlanPercentage,
            'max_first_pay_days' => $maxFirstPayDays,
        ]);
    }

    #[Test]
    public function it_can_update_all_offer_fields_for_subclient(): void
    {
        Queue::fake();

        $this->subclient = Subclient::factory()->create();

        Livewire::test(UpdatePage::class, ['record' => $this->subclient])
            ->set([
                'form.pif_balance_discount_percent' => $pif = 30,
                'form.ppa_balance_discount_percent' => $ppa = 30,
                'form.min_monthly_pay_percent' => $minMonthly = 20,
                'form.max_days_first_pay' => $maxDays = 30,
                'form.minimum_settlement_percentage' => $minSettlementPercentage = 10,
                'form.minimum_payment_plan_percentage' => $minPaymentPlanPercentage = 10,
                'form.max_first_pay_days' => $maxFirstPayDays = 100,
            ])
            ->call('update')
            ->assertOk()
            ->assertHasNoErrors();

        Queue::assertNotPushed(TriggerEmailAndSmsServiceJob::class);

        $this->assertDatabaseHas(Subclient::class, [
            'id' => $this->subclient->id,
            'pif_balance_discount_percent' => $pif,
            'ppa_balance_discount_percent' => $ppa,
            'min_monthly_pay_percent' => $minMonthly,
            'max_days_first_pay' => $maxDays,
            'minimum_settlement_percentage' => $minSettlementPercentage,
            'minimum_payment_plan_percentage' => $minPaymentPlanPercentage,
            'max_first_pay_days' => $maxFirstPayDays,
        ]);
    }

    #[Test]
    public function it_can_show_validation_error_when_min_settlement_grater_than_pif_balance_discount_percent(): void
    {
        Consumer::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_discount_percent' => fake()->numberBetween(0, 99),
                'pay_setup_discount_percent' => fake()->numberBetween(0, 99),
                'min_monthly_pay_percent' => fake()->numberBetween(1, 99),
                'max_days_first_pay' => fake()->numberBetween(1, 30),
                'minimum_settlement_percentage' => fake()->numberBetween(2, 30),
                'minimum_payment_plan_percentage' => fake()->numberBetween(2, 30),
                'max_first_pay_days' => fake()->numberBetween(100, 999),
            ]);

        Livewire::test(UpdatePage::class, ['record' => $this->consumer])
            ->set('form.pif_discount_percent', 30)
            ->set('form.pay_setup_discount_percent', 30)
            ->set('form.min_monthly_pay_percent', 20)
            ->set('form.max_days_first_pay', 40)
            ->set('form.minimum_settlement_percentage', 31)
            ->set('form.minimum_payment_plan_percentage', 10)
            ->set('form.max_first_pay_days', 100)
            ->call('update')
            ->assertHasErrors([
                'form.minimum_settlement_percentage' => ['lt:pif_discount_percent'],
            ])
            ->assertHasNoErrors('form.pay_terms')
            ->assertOk();
    }

    #[Test]
    public function it_can_show_validation_error_when_min_payment_plan_grater_than_min_monthly_pay_percent(): void
    {
        Consumer::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_discount_percent' => fake()->numberBetween(0, 99),
                'pay_setup_discount_percent' => fake()->numberBetween(0, 99),
                'min_monthly_pay_percent' => fake()->numberBetween(1, 99),
                'max_days_first_pay' => fake()->numberBetween(1, 30),
                'minimum_settlement_percentage' => fake()->numberBetween(2, 30),
                'minimum_payment_plan_percentage' => fake()->numberBetween(2, 30),
                'max_first_pay_days' => fake()->numberBetween(100, 999),
            ]);

        Livewire::test(UpdatePage::class, ['record' => $this->consumer])
            ->set([
                'form.pif_discount_percent' => 30,
                'form.pay_setup_discount_percent' => 30,
                'form.min_monthly_pay_percent' => 20,
                'form.max_days_first_pay' => 40,
                'form.minimum_settlement_percentage' => 25,
                'form.minimum_payment_plan_percentage' => 21,
                'form.max_first_pay_days' => 100,
            ])
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
        Consumer::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'pif_discount_percent' => fake()->numberBetween(0, 99),
                'pay_setup_discount_percent' => fake()->numberBetween(0, 99),
                'min_monthly_pay_percent' => fake()->numberBetween(1, 99),
                'max_days_first_pay' => fake()->numberBetween(1, 30),
                'minimum_settlement_percentage' => fake()->numberBetween(2, 30),
                'minimum_payment_plan_percentage' => fake()->numberBetween(2, 30),
                'max_first_pay_days' => fake()->numberBetween(100, 999),
            ]);

        Livewire::test(UpdatePage::class, ['record' => $this->consumer])
            ->set([
                'form.pif_discount_percent' => 30,
                'form.pay_setup_discount_percent' => 30,
                'form.min_monthly_pay_percent' => 20,
                'form.max_days_first_pay' => 40,
                'form.minimum_settlement_percentage' => 25,
                'form.minimum_payment_plan_percentage' => 15,
                'form.max_first_pay_days' => 35,
            ])
            ->call('update')
            ->assertHasErrors([
                'form.max_first_pay_days' => ['gt:max_days_first_pay'],
            ])
            ->assertHasNoErrors('form.pay_terms')
            ->assertOk();
    }

    public static function validationRule(): array
    {
        return [
            [
                [
                    'form.pif_discount_percent' => 'abc',
                    'form.pay_setup_discount_percent' => 'abc',
                    'form.min_monthly_pay_percent' => 'abc',
                    'form.max_days_first_pay' => 'abc',
                    'form.minimum_settlement_percentage' => 'abc',
                    'form.minimum_payment_plan_percentage' => 'abc',
                    'form.max_first_pay_days' => 'abc',
                ],
                [
                    'form.pif_discount_percent' => ['integer'],
                    'form.pay_setup_discount_percent' => ['integer'],
                    'form.min_monthly_pay_percent' => ['integer'],
                    'form.max_days_first_pay' => ['integer'],
                    'form.minimum_settlement_percentage' => ['integer'],
                    'form.minimum_payment_plan_percentage' => ['integer'],
                    'form.max_first_pay_days' => ['integer'],
                ],
            ],
            [
                [
                    'form.pif_discount_percent' => -1,
                    'form.pay_setup_discount_percent' => -1,
                    'form.min_monthly_pay_percent' => -1,
                    'form.max_days_first_pay' => -1,
                    'form.minimum_settlement_percentage' => -1,
                    'form.minimum_payment_plan_percentage' => -1,
                    'form.max_first_pay_days' => -1,
                ],
                [
                    'form.pif_discount_percent' => ['min:0'],
                    'form.pay_setup_discount_percent' => ['min:0'],
                    'form.min_monthly_pay_percent' => ['min:0'],
                    'form.max_days_first_pay' => ['min:1'],
                    'form.minimum_settlement_percentage' => ['min:1'],
                    'form.minimum_payment_plan_percentage' => ['min:1'],
                    'form.max_first_pay_days' => ['min:1'],
                ],
            ],
            [
                [
                    'form.pif_discount_percent' => 10000,
                    'form.pay_setup_discount_percent' => 10000,
                    'form.min_monthly_pay_percent' => 10000,
                    'form.max_days_first_pay' => 10000,
                    'form.minimum_settlement_percentage' => 1000,
                    'form.minimum_payment_plan_percentage' => 1000,
                    'form.max_first_pay_days' => 1111,
                ],
                [
                    'form.pif_discount_percent' => ['max:100'],
                    'form.pay_setup_discount_percent' => ['max:100'],
                    'form.min_monthly_pay_percent' => ['max:100'],
                    'form.max_days_first_pay' => ['max:1000'],
                    'form.minimum_settlement_percentage' => ['max:100'],
                    'form.minimum_payment_plan_percentage' => ['max:100'],
                    'form.max_first_pay_days' => ['max:1000'],
                ],
            ],
            [
                [
                    'form.pif_discount_percent' => '-0',
                    'form.pay_setup_discount_percent' => '-0',
                    'form.min_monthly_pay_percent' => '-0',
                    'form.max_days_first_pay' => '',
                    'form.minimum_settlement_percentage' => '-0',
                    'form.minimum_payment_plan_percentage' => '-0',
                    'form.max_first_pay_days' => '',
                ],
                [
                    'form.pif_discount_percent' => ['regex'],
                    'form.pay_setup_discount_percent' => ['regex'],
                    'form.min_monthly_pay_percent' => ['regex'],
                    'form.minimum_settlement_percentage' => ['regex'],
                    'form.minimum_payment_plan_percentage' => ['regex'],
                    'form.max_first_pay_days' => ['required'],
                ],
            ],
            [
                [
                    'form.pif_discount_percent' => 30,
                    'form.pay_setup_discount_percent' => 23,
                    'form.min_monthly_pay_percent' => '',
                    'form.max_days_first_pay' => 12,
                    'form.minimum_settlement_percentage' => '',
                    'form.minimum_payment_plan_percentage' => '',
                    'form.max_first_pay_days' => '',
                ],
                [
                    'form.min_monthly_pay_percent' => ['required'],
                    'form.minimum_settlement_percentage' => ['required'],
                    'form.minimum_payment_plan_percentage' => ['required'],
                    'form.max_first_pay_days' => ['required'],
                    'form.minimum_settlement_percentage' => ['required'],
                    'form.minimum_payment_plan_percentage' => ['required'],
                    'form.max_first_pay_days' => ['required'],
                ],
            ],
            [
                [
                    'form.pif_discount_percent' => 30,
                    'form.pay_setup_discount_percent' => '',
                    'form.min_monthly_pay_percent' => 23,
                    'form.max_days_first_pay' => 12,
                    'form.minimum_settlement_percentage' => '',
                    'form.minimum_payment_plan_percentage' => '',
                    'form.max_first_pay_days' => '',
                ],
                [
                    'form.pay_setup_discount_percent' => ['required'],
                    'form.minimum_settlement_percentage' => ['required'],
                    'form.minimum_payment_plan_percentage' => ['required'],
                    'form.max_first_pay_days' => ['required'],
                    'form.minimum_settlement_percentage' => ['required'],
                    'form.minimum_payment_plan_percentage' => ['required'],
                    'form.max_first_pay_days' => ['required'],
                ],
            ],
        ];
    }
}
