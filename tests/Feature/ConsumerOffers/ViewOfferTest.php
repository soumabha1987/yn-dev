<?php

declare(strict_types=1);

namespace Tests\Feature\ConsumerOffers;

use App\Console\Commands\CommunicationStatusCommand;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\CompanyMembershipStatus;
use App\Enums\CompanyStatus;
use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Creditor\ConsumerOffers\ViewOffer;
use App\Models\AutomatedCommunicationHistory;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ConsumerProfile;
use App\Models\ConsumerUnsubscribe;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ViewOfferTest extends TestCase
{
    public User $user;

    public Consumer $consumer;

    public ConsumerNegotiation $consumerNegotiation;

    public CompanyMembership $companyMembership;

    public int $getMultiplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->for(Company::factory()->create(['status' => CompanyStatus::ACTIVE]))
            ->create();

        $this->actingAs($this->user);

        Merchant::factory()->create(['company_id' => $this->user->company_id, 'subclient_id' => null]);

        $this->companyMembership = CompanyMembership::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => CompanyMembershipStatus::ACTIVE,
            'cancelled_at' => null,
        ]);

        $consumerProfile = ConsumerProfile::query()->create([
            'email' => fake()->safeEmail(),
            'mobile' => fake()->phoneNumber(),
            'text_permission' => false,
            'email_permission' => true,
        ]);

        $commonConsumer = [
            'last_name' => 'test',
            'dob' => Carbon::create('1999', '11', '11'),
            'last4ssn' => '1111',
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'current_balance' => 100,
            'custom_offer' => true,
        ];

        $commonConsumerNegotiation = [
            'company_id' => $this->user->company_id,
            'active_negotiation' => true,
            'installment_type' => 'monthly',
            'payment_plan_current_balance' => null,
            'first_pay_date' => now(),
        ];

        $this->consumer = Consumer::factory()->for($consumerProfile)->create([
            ...$commonConsumer,
            'first_name' => 'Counter offer',
            'max_days_first_pay' => 5,
            'counter_offer' => true,
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'offer_accepted' => false,
        ]);

        $this->consumerNegotiation = ConsumerNegotiation::factory()
            ->for($this->consumer)
            ->create([
                ...$commonConsumerNegotiation,
                'offer_accepted' => false,
                'negotiation_type' => NegotiationType::PIF->value,
                'one_time_settlement' => fake()->numberBetween(1, 99 - 1),
                'no_of_installments' => 1,
                'counter_first_pay_date' => now(),
                'counter_one_time_amount' => 100,
                'counter_no_of_installments' => 1,
            ]);

        $this->getMultiplication = match ($this->consumerNegotiation->installment_type) {
            InstallmentType::WEEKLY => 4,
            InstallmentType::BIMONTHLY => 2,
            InstallmentType::MONTHLY => 1,
        };
    }

    #[Test]
    public function it_can_render_livewire_page_when_negotiation_type_is_pif(): void
    {
        $this->travelTo(now()->addMinutes(10));

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->assertSet('consumerNegotiation.consumer_id', $this->consumer->id)
            ->assertSet('calculatedData.offer.first_payment_date.timestamp', now()->addDays($this->consumer->max_days_first_pay)->timestamp)
            ->assertSet('calculatedData.offer.first_payment_day', $this->consumer->max_days_first_pay)
            ->assertSet('calculatedData.consumer_offer.first_payment_date.timestamp', $this->consumerNegotiation->first_pay_date->timestamp)
            ->assertSet('calculatedData.consumer_offer.settlement_discount_offer_amount', $this->consumerNegotiation->one_time_settlement)
            ->assertSet('calculatedData.creditor_offer.first_payment_date.timestamp', $this->consumerNegotiation->counter_first_pay_date->timestamp)
            ->assertSet('calculatedData.creditor_offer.settlement_discount_offer_amount', $this->consumerNegotiation->counter_one_time_amount)
            ->assertSet('calculatedData.consumer_offer.minimum_monthly_payment', null)
            ->assertSet('calculatedData.creditor_offer.minimum_monthly_payment', null)
            ->assertOk()
            ->assertViewIs('livewire.creditor.consumer-offers.view-offer');
    }

    #[Test]
    public function it_can_render_livewire_page_when_negotiation_type_is_installment(): void
    {
        $this->travelTo(now()->addMinutes(10));

        $amount = $this->installmentSetup();

        $negotiationAmount = $amount - ($amount * $this->consumer->pay_setup_discount_percent / 100);

        $minimumMonthlyPaymentForConsumer = ((float) $this->consumerNegotiation->monthly_amount) * $this->getMultiplication;
        $minimumMonthlyPaymentForCreditor = ((float) $this->consumerNegotiation->counter_monthly_amount) * $this->getMultiplication;

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->assertSet('consumerNegotiation.consumer_id', $this->consumer->id)
            ->assertSet('calculatedData.offer.first_payment_date.timestamp', now()->addDays($this->consumer->max_days_first_pay)->timestamp)
            ->assertSet('calculatedData.offer.first_payment_day', $this->consumer->max_days_first_pay)
            ->assertSet('calculatedData.offer.minimum_monthly_payment', $negotiationAmount / $this->consumer->min_monthly_pay_percent)
            ->assertSet('calculatedData.offer.minimum_monthly_payment_percentage', $this->consumer->min_monthly_pay_percent)
            ->assertSet('calculatedData.offer.payment_plan_offer_amount', $negotiationAmount)
            ->assertSet('calculatedData.offer.payment_plan_offer_percentage', $this->consumer->pay_setup_discount_percent)
            ->assertSet('calculatedData.consumer_offer.payment_plan_offer_amount', $this->consumerNegotiation->negotiate_amount)
            ->assertSet('calculatedData.consumer_offer.minimum_monthly_payment', $minimumMonthlyPaymentForConsumer)
            ->assertSet('calculatedData.creditor_offer.payment_plan_offer_amount', $this->consumerNegotiation->counter_negotiate_amount)
            ->assertSet('calculatedData.creditor_offer.minimum_monthly_payment', $minimumMonthlyPaymentForCreditor)
            ->assertOk();
    }

    #[Test]
    public function it_can_accept_offer_when_negotiation_type_is_pif(): void
    {
        Queue::fake();

        $this->travelTo(now()->addMinutes(10));

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->call('acceptOffer')
            ->assertDispatched('close-dialog')
            ->assertOk();

        $this->assertDatabaseHas(Consumer::class, [
            'id' => $this->consumer->id,
            'offer_accepted' => true,
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
        ]);

        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'id' => $this->consumerNegotiation->id,
            'offer_accepted' => (int) true,
            'offer_accepted_at' => now()->toDateTimeString(),
            'approved_by' => (string) $this->user->id,
        ]);

        $this->assertDatabaseCount(ScheduleTransaction::class, 1);

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
        );
    }

    #[Test]
    public function it_can_accept_offer_when_negotiation_type_is_installments(): void
    {
        Queue::fake();

        $this->travelTo(now()->addMinutes(10));

        $this->installmentSetup();

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->call('acceptOffer')
            ->assertDispatched('close-dialog')
            ->assertOk();

        $this->assertDatabaseHas(Consumer::class, [
            'id' => $this->consumer->id,
            'offer_accepted' => true,
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
        ]);

        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'id' => $this->consumerNegotiation->id,
            'offer_accepted' => (int) true,
            'offer_accepted_at' => now()->toDateTimeString(),
            'approved_by' => (string) $this->user->id,
        ]);

        $this->assertDatabaseCount(ScheduleTransaction::class, 10);

        $match = match ($this->consumerNegotiation->installment_type) {
            InstallmentType::WEEKLY => 'addWeek',
            InstallmentType::BIMONTHLY => 'addBimonthly',
            InstallmentType::MONTHLY => 'addMonthsNoOverflow',
        };

        $this->assertDatabaseHas(ScheduleTransaction::class, [
            'consumer_id' => $this->consumer->id,
            'subclient_id' => $this->consumer->subclient_id,
            'company_id' => $this->consumer->company_id,
            'schedule_date' => $this->consumerNegotiation->first_pay_date->{$match}()->toDateString(),
            'amount' => number_format((float) $this->consumerNegotiation->monthly_amount, 2, thousands_separator: ''),
        ]);

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
        );
    }

    #[Test]
    public function it_can_send_counter_offer_when_pif_negotiation_type(): void
    {
        Queue::fake();

        CommunicationStatus::factory()
            ->for(AutomatedTemplate::factory()->for($this->user), 'emailTemplate')
            ->create([
                'code' => CommunicationCode::COUNTER_OFFER_BUT_NO_RESPONSE,
                'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
            ]);

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->assertSet('form.settlement_discount_amount', $this->consumerNegotiation->counter_one_time_amount)
            ->assertSet('form.counter_first_pay_date', $this->consumerNegotiation->counter_first_pay_date->toDateString())
            ->assertSet('form.monthly_amount', '')
            ->assertSet('form.counter_note', $this->consumerNegotiation->counter_note)
            ->set('form.settlement_discount_amount', $amount = 120.00)
            ->set('form.counter_first_pay_date', $firstPayDate = now()->addDay()->toDateString())
            ->set('form.counter_note', $counterNote = fake()->sentence())
            ->call('submitCounterOffer')
            ->assertHasNoErrors()
            ->assertOk();

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 1);

        $this->assertEquals($this->consumer->refresh()->counter_offer, true);
        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'id' => $this->consumerNegotiation->id,
            'counter_one_time_amount' => $amount,
            'counter_negotiate_amount' => null,
            'counter_monthly_amount' => null,
            'counter_first_pay_date' => $firstPayDate . ' 00:00:00',
            'counter_last_month_amount' => null,
            'counter_no_of_installments' => null,
            'counter_note' => $counterNote,
        ]);
    }

    #[Test]
    public function it_can_send_counter_offer_of_unsubscribe_consumer(): void
    {
        Queue::fake();

        ConsumerUnsubscribe::factory()->create([
            'company_id' => $this->consumer->company_id,
            'consumer_id' => $this->consumer->id,
            'email' => $this->consumer->email1,
        ]);

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->assertSet('form.settlement_discount_amount', $this->consumerNegotiation->counter_one_time_amount)
            ->assertSet('form.counter_first_pay_date', $this->consumerNegotiation->counter_first_pay_date->toDateString())
            ->assertSet('form.monthly_amount', '')
            ->assertSet('form.counter_note', $this->consumerNegotiation->counter_note)
            ->set('form.settlement_discount_amount', $amount = 120.00)
            ->set('form.counter_first_pay_date', $firstPayDate = now()->addDay()->toDateString())
            ->set('form.counter_note', $counterNote = fake()->sentence())
            ->call('submitCounterOffer')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertEquals($this->consumer->refresh()->counter_offer, true);
        $this->assertDatabaseCount(AutomatedCommunicationHistory::class, 0);
        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'id' => $this->consumerNegotiation->id,
            'counter_one_time_amount' => $amount,
            'counter_negotiate_amount' => null,
            'counter_monthly_amount' => null,
            'counter_first_pay_date' => $firstPayDate . ' 00:00:00',
            'counter_last_month_amount' => null,
            'counter_no_of_installments' => null,
            'counter_note' => $counterNote,
        ]);

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 1);
    }

    #[Test]
    public function it_can_declined_offer(): void
    {
        Queue::fake();

        Artisan::call(CommunicationStatusCommand::class);

        $automatedTemplate = AutomatedTemplate::factory()->create(['user_id' => $this->user->id]);

        $communicationStatus = CommunicationStatus::query()->firstWhere('code', CommunicationCode::OFFER_DECLINED);

        $communicationStatus->update([
            'automated_email_template_id' => $automatedTemplate->id,
            'automated_sms_template_id' => $automatedTemplate->id,
        ]);

        $paymentProfile = PaymentProfile::factory()->for($this->consumer)->create();

        $consumerProfile = ConsumerProfile::factory()
            ->create([
                'email_permission' => true,
                'text_permission' => true,
                'email' => 'test@test.com',
            ]);

        $this->consumer->update(['consumer_profile_id' => $consumerProfile->id]);

        $scheduleTransaction = ScheduleTransaction::factory()->for($this->consumer)->create();

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->call('declineOffer', $this->consumer)
            ->assertOk()
            ->assertViewIs('livewire.creditor.consumer-offers.view-offer')
            ->assertSee(__('Decline'));

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 1);

        $this->assertSoftDeleted($paymentProfile);
        $this->assertModelMissing($scheduleTransaction);
        $this->assertEquals($this->consumer->refresh()->status, ConsumerStatus::PAYMENT_DECLINED);
        $this->assertEquals($this->consumer->consumerNegotiation->offer_accepted, false);
    }

    #[Test]
    public function it_can_declined_offer_for_unsubscribe_consumer(): void
    {
        Queue::fake();

        Artisan::call(CommunicationStatusCommand::class);

        $automatedTemplate = AutomatedTemplate::factory()->create(['user_id' => $this->user->id]);

        $communicationStatus = CommunicationStatus::query()->firstWhere('code', CommunicationCode::OFFER_DECLINED);

        $communicationStatus->update([
            'automated_email_template_id' => $automatedTemplate->id,
            'automated_sms_template_id' => $automatedTemplate->id,
        ]);

        $consumerProfile = ConsumerProfile::query()->create(['email' => 'test@test.com']);

        $this->consumer->update(['consumer_profile_id' => $consumerProfile->id]);

        $scheduleTransaction = ScheduleTransaction::factory()->for($this->consumer)->create();

        ConsumerUnsubscribe::factory()->create([
            'company_id' => $this->consumer->company_id,
            'consumer_id' => $this->consumer->id,
            'email' => $this->consumer->email1,
        ]);

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->call('declineOffer', $this->consumer)
            ->assertViewIs('livewire.creditor.consumer-offers.view-offer')
            ->assertSee(__('Decline'))
            ->assertDispatched('close-dialog')
            ->assertOk();

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 1);

        $this->assertModelMissing($scheduleTransaction);

        $this->assertEquals($this->consumer->refresh()->status, ConsumerStatus::PAYMENT_DECLINED);
        $this->assertEquals($this->consumer->consumerNegotiation->offer_accepted, false);
    }

    #[Test]
    #[DataProvider('requestValidation')]
    public function it_can_send_counter_offer_validation(NegotiationType $negotiationType, array $requestedData, array $requestedErrors): void
    {
        Queue::fake();

        $this->consumerNegotiation->update(['negotiation_type' => $negotiationType]);

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->set($requestedData)
            ->call('submitCounterOffer')
            ->assertHasErrors($requestedErrors)
            ->assertOk();
    }

    private function installmentSetup(): int
    {
        $amount = 100;

        $this->consumer->update([
            'payment_setup' => true,
            'pay_setup_discount_percent' => 10,
            'min_monthly_pay_percent' => 10,
        ]);

        $this->consumerNegotiation->update([
            'offer_accepted' => false,
            'negotiation_type' => NegotiationType::INSTALLMENT->value,
            'negotiate_amount' => $amount,
            'no_of_installments' => 10,
            'monthly_amount' => $amount / 10,
            'last_month_amount' => $amount % 10,
        ]);

        return $amount;
    }

    public static function requestValidation(): array
    {
        return [
            [
                NegotiationType::PIF,
                [
                    'form.settlement_discount_amount' => '',
                    'form.counter_first_pay_date' => '',
                ],
                [
                    'form.settlement_discount_amount' => ['required'],
                    'form.counter_first_pay_date' => ['required'],
                ],
            ],
            [
                NegotiationType::INSTALLMENT,
                [
                    'form.payment_plan_discount_amount' => '',
                    'form.monthly_amount' => '',
                    'form.counter_first_pay_date' => '',
                ],
                [
                    'form.payment_plan_discount_amount' => ['required'],
                    'form.monthly_amount' => ['required'],
                    'form.counter_first_pay_date' => ['required'],
                ],
            ],
            [
                NegotiationType::PIF,
                [
                    'form.settlement_discount_amount' => fake()->word(),
                    'form.counter_first_pay_date' => fake()->word(),
                ],
                [
                    'form.settlement_discount_amount' => ['numeric'],
                    'form.counter_first_pay_date' => ['date'],
                ],
            ],
            [
                NegotiationType::INSTALLMENT,
                [
                    'form.payment_plan_discount_amount' => fake()->word(),
                    'form.monthly_amount' => fake()->word(),
                    'form.counter_first_pay_date' => fake()->word(),
                ],
                [
                    'form.payment_plan_discount_amount' => ['numeric'],
                    'form.monthly_amount' => ['numeric'],
                    'form.counter_first_pay_date' => ['date'],
                ],
            ],
            [
                NegotiationType::PIF,
                [
                    'form.settlement_discount_amount' => -0,
                    'form.counter_first_pay_date' => fake()->date(),
                ],
                [
                    'form.settlement_discount_amount' => ['gt:0'],
                    'form.counter_first_pay_date' => ['after_or_equal:today'],
                ],
            ],
            [
                NegotiationType::INSTALLMENT,
                [
                    'form.payment_plan_discount_amount' => -0,
                    'form.monthly_amount' => -0,
                    'form.counter_first_pay_date' => today()->addDay()->format('M d, Y'),
                ],
                [
                    'form.payment_plan_discount_amount' => ['gt:0'],
                    'form.monthly_amount' => ['gt:0'],
                    'form.counter_first_pay_date' => ['date_format:Y-m-d'],
                ],
            ],
        ];
    }
}
