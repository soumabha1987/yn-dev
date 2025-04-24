<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\MyAccount;

use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Livewire\Consumer\MyAccount\ViewOffer;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ViewOfferTest extends TestCase
{
    protected Company $company;

    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $this->consumer = Consumer::factory()
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'status' => ConsumerStatus::PAYMENT_SETUP,
                'counter_offer' => true,
                'current_balance' => 100,
                'pay_setup_discount_percent' => 30,
                'min_monthly_pay_percent' => 25,
                'max_days_first_pay' => 20,
                'minimum_settlement_percentage' => 10,
                'minimum_payment_plan_percentage' => 5,
                'max_first_pay_days' => 100,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');

        CompanyMembership::factory()->create(['company_id' => $this->consumer->company_id]);
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view_and_data(): void
    {
        ConsumerNegotiation::factory()
            ->for($this->consumer)
            ->create([
                'one_time_settlement' => 60,
                'active_negotiation' => true,
                'offer_accepted' => false,
                'counter_offer_accepted' => false,
                'negotiation_type' => NegotiationType::INSTALLMENT,
                'counter_one_time_amount' => 100,
                'payment_plan_current_balance' => 100,
                'counter_monthly_amount' => 20,
                'counter_negotiate_amount' => 100,
                'counter_first_pay_date' => $date = today()->addDay()->toDateString(),
                'monthly_amount' => 10,
                'negotiate_amount' => 100,
                'first_pay_date' => $date,
            ]);

        $offerDetails = [];

        Livewire::test(ViewOffer::class, [
            'consumer' => $this->consumer,
            'view' => fake()->randomElement(['card', 'grid']),
        ])
            ->assertOk()
            ->assertViewIs('livewire.consumer.my-account.view-offer')
            ->assertViewHas('consumer', function (Consumer $consumer) use (&$offerDetails): bool {
                $offerDetails = $consumer->offerDetails;

                return $this->consumer->is($consumer);
            });

        $this->assertArrayHasKey('account_profile_details', $offerDetails);
        $this->assertArrayHasKey('account_number', $offerDetails['account_profile_details']);
        $this->assertEquals($this->consumer->account_number, $offerDetails['account_profile_details']['account_number']);
        $this->assertArrayHasKey('creditor_name', $offerDetails['account_profile_details']);
        $this->assertEquals($this->consumer->company->company_name, $offerDetails['account_profile_details']['creditor_name']);
        $this->assertArrayHasKey('current_balance', $offerDetails['account_profile_details']);
        $this->assertEquals(100, $offerDetails['account_profile_details']['current_balance']);
    }

    #[Test]
    public function it_can_below_five_percentage_amount_counter_offer_on_installment_type(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'counter_offer' => true,
            'current_balance' => 100,
            'pay_setup_discount_percent' => 30,
            'min_monthly_pay_percent' => 20,
            'max_days_first_pay' => 20,
        ]);

        $consumerNegotiation = ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
            'active_negotiation' => true,
            'offer_accepted' => false,
            'counter_offer_accepted' => false,
            'negotiation_type' => NegotiationType::INSTALLMENT,
            'counter_one_time_amount' => 100,
            'counter_monthly_amount' => 20,
            'counter_negotiate_amount' => 100,
            'counter_first_pay_date' => $date = today()->addDay()->toDateString(),
            'monthly_amount' => 10,
            'negotiate_amount' => 100,
            'first_pay_date' => $date,
        ]);

        Livewire::test(ViewOffer::class, [
            'consumer' => $this->consumer,
            'view' => fake()->randomElement(['card', 'grid']),
        ])
            ->set('form.counter_first_pay_date', today()->addDays(2)->toDateString())
            ->set('form.monthly_amount', $amount = 0.50)
            ->set('form.counter_note', $note = fake()->word())
            ->call('submitCounterOffer')
            ->assertOk()
            ->assertHasErrors('form.monthly_amount')
            ->assertNotDispatched('close-dialog-of-counter-offer');

        $this->assertDatabaseMissing(ConsumerNegotiation::class, [
            'id' => $consumerNegotiation->id,
            'counter_one_time_amount' => null,
            'counter_monthly_amount' => null,
            'counter_negotiate_amount' => null,
            'counter_first_pay_date' => null,
            'counter_no_of_installments' => null,
            'monthly_amount' => null,
            'negotiate_amount' => null,
            'first_pay_date' => today()->addDays(2)->format('Y-m-d H:i:s'),
            'note' => $note,
            'no_of_installments' => null,
            'one_time_settlement' => $amount,
        ]);
    }

    #[Test]
    public function it_can_not_accept_payment(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'counter_offer' => true,
        ]);

        $consumerNegotiation = ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
            'active_negotiation' => true,
            'offer_accepted' => false,
            'counter_offer_accepted' => false,
        ]);

        Livewire::test(ViewOffer::class, [
            'consumer' => $this->consumer,
            'view' => fake()->randomElement(['grid', 'card']),
        ])
            ->call('acceptPayment')
            ->assertRedirectToRoute('consumer.payment', ['consumer' => $this->consumer->id])
            ->assertOk();

        $this->assertTrue($consumerNegotiation->refresh()->counter_offer_accepted);
        $this->assertEquals($this->consumer->first_name . ' ' . $this->consumer->last_name, $consumerNegotiation->approved_by);
        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $this->consumer->refresh()->status);
        $this->assertTrue($this->consumer->offer_accepted);
    }

    #[Test]
    public function it_can_accept_installment_payment_if_already_exists_then_not_create_new_one(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'counter_offer' => true,
        ]);

        ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
            'active_negotiation' => true,
            'offer_accepted' => false,
            'counter_offer_accepted' => false,
            'negotiation_type' => NegotiationType::INSTALLMENT,
            'installment_type' => InstallmentType::MONTHLY,
            'counter_monthly_amount' => 11.2,
            'counter_first_pay_date' => now()->addDays(3)->toDateString(),
            'counter_no_of_installments' => 12,
            'counter_last_month_amount' => 12,
        ]);

        $paymentProfile = PaymentProfile::factory()->create(['consumer_id' => $this->consumer->id]);

        $scheduleTransaction = ScheduleTransaction::factory()->create([
            'status' => TransactionStatus::SCHEDULED,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'payment_profile_id' => $paymentProfile->id,
        ]);

        Livewire::test(ViewOffer::class, [
            'consumer' => $this->consumer,
            'view' => fake()->randomElement(['card', 'grid']),
        ])
            ->call('acceptPayment')
            ->assertRedirect(route('consumer.schedule_plan', ['consumer' => $this->consumer->id]))
            ->assertOk();

        $this->assertModelMissing($scheduleTransaction);

        $date = today()->addDays(3);

        $scheduleDate = $date->isSameDay(today()->endOfMonth())
            ? $date->addMonthNoOverflow()->endOfMonth()->toDateString()
            : $date->addMonthNoOverflow()->toDateString();

        $this->assertDatabaseHas(ScheduleTransaction::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'schedule_date' => $scheduleDate,
            'amount' => 11.2,
            'payment_profile_id' => $paymentProfile->id,
            'status' => TransactionStatus::SCHEDULED,
            'status_code' => '111',
            'transaction_type' => NegotiationType::INSTALLMENT,
            'stripe_payment_detail_id' => null,
        ]);

        $this->assertDatabaseCount(ScheduleTransaction::class, 13);
    }

    #[Test]
    public function it_can_accept_pif_payment(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'counter_offer' => true,
        ]);

        $consumerNegotiation = ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
            'active_negotiation' => true,
            'offer_accepted' => false,
            'counter_offer_accepted' => false,
            'negotiation_type' => NegotiationType::PIF,
            'counter_one_time_amount' => 100.23,
        ]);

        $paymentProfile = PaymentProfile::factory()->create(['consumer_id' => $this->consumer->id]);

        $scheduleTransaction = ScheduleTransaction::factory()->create([
            'status' => TransactionStatus::SCHEDULED,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'payment_profile_id' => $paymentProfile->id,
        ]);

        Livewire::test(ViewOffer::class, [
            'consumer' => $this->consumer,
            'view' => fake()->randomElement(['card', 'grid']),
        ])
            ->call('acceptPayment')
            ->assertRedirect(route('consumer.schedule_plan', ['consumer' => $this->consumer->id]))
            ->assertOk();

        $this->assertModelMissing($scheduleTransaction);

        $this->assertTrue($consumerNegotiation->refresh()->counter_offer_accepted);
        $this->assertNotNull($consumerNegotiation->approved_by);
        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $this->consumer->refresh()->status);
        $this->assertTrue($this->consumer->offer_accepted);

        $this->assertDatabaseHas(ScheduleTransaction::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'payment_profile_id' => $paymentProfile->id,
            'status' => TransactionStatus::SCHEDULED,
            'status_code' => '111',
            'amount' => 100.23,
            'transaction_type' => NegotiationType::PIF,
            'stripe_payment_detail_id' => null,
        ]);
    }

    #[Test]
    public function it_can_send_counter_offer_on_installment_type(): void
    {
        $consumerNegotiation = ConsumerNegotiation::factory()
            ->for($this->consumer)
            ->create([
                'active_negotiation' => true,
                'offer_accepted' => false,
                'counter_offer_accepted' => false,
                'negotiation_type' => NegotiationType::INSTALLMENT,
                'counter_one_time_amount' => 100,
                'counter_monthly_amount' => 20,
                'counter_negotiate_amount' => 100,
                'counter_first_pay_date' => $date = today()->addDay()->toDateString(),
                'monthly_amount' => 10,
                'negotiate_amount' => 100,
                'first_pay_date' => $date,
            ]);

        Livewire::test(ViewOffer::class, [
            'consumer' => $this->consumer,
            'view' => fake()->randomElement(['card', 'grid']),
        ])
            ->set('form.counter_first_pay_date', today()->addDays(2)->toDateString())
            ->set('form.monthly_amount', $monthlyAmount = 4)
            ->set('form.counter_note', $note = fake()->word())
            ->call('submitCounterOffer')
            ->assertHasNoErrors()
            ->assertOk()
            ->assertDispatched('close-dialog-of-counter-offer');

        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'id' => $consumerNegotiation->id,
            'counter_one_time_amount' => null,
            'counter_monthly_amount' => null,
            'counter_negotiate_amount' => null,
            'counter_first_pay_date' => null,
            'counter_no_of_installments' => null,
            'counter_last_month_amount' => null,
            'monthly_amount' => Number::format($monthlyAmount, 2),
            'negotiate_amount' => $consumerNegotiation->negotiate_amount,
            'first_pay_date' => today()->addDays(2)->toDateTimeString(),
            'note' => $note,
            'no_of_installments' => 16,
            'last_month_amount' => 6.00,
        ]);

        $this->assertDatabaseHas(Consumer::class, [
            'offer_accepted' => false,
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'custom_offer' => true,
            'counter_offer' => false,
        ]);
    }

    #[Test]
    public function it_can_auto_approved_counter_offer_in_installment_type(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'counter_offer' => true,
            'current_balance' => 100,
            'pay_setup_discount_percent' => 30,
            'min_monthly_pay_percent' => 20,
            'max_days_first_pay' => 20,
        ]);

        $consumerNegotiation = ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
            'active_negotiation' => true,
            'offer_accepted' => false,
            'counter_offer_accepted' => false,
            'negotiation_type' => NegotiationType::INSTALLMENT,
            'counter_one_time_amount' => 100,
            'counter_monthly_amount' => 20,
            'counter_negotiate_amount' => 100,
            'counter_first_pay_date' => $date = today()->addDay()->toDateString(),
            'monthly_amount' => 10,
            'negotiate_amount' => 100,
            'first_pay_date' => $date,
        ]);

        Livewire::test(ViewOffer::class, [
            'consumer' => $this->consumer,
            'view' => fake()->randomElement(['card', 'grid']),
        ])
            ->set('form.counter_first_pay_date', today()->addDays(2)->toDateString())
            ->set('form.monthly_amount', $monthlyAmount = 20)
            ->set('form.counter_note', $note = fake()->word())
            ->call('submitCounterOffer')
            ->assertHasNoErrors()
            ->assertOk()
            ->assertDispatched('close-dialog-of-counter-offer');

        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'id' => $consumerNegotiation->id,
            'counter_one_time_amount' => null,
            'counter_monthly_amount' => null,
            'counter_negotiate_amount' => null,
            'counter_first_pay_date' => null,
            'counter_no_of_installments' => null,
            'monthly_amount' => Number::format($monthlyAmount, 2),
            'negotiate_amount' => $consumerNegotiation->negotiate_amount,
            'first_pay_date' => today()->addDays(2)->format('Y-m-d H:i:s'),
            'note' => $note,
            'no_of_installments' => 3,
        ]);

        $this->assertDatabaseHas(Consumer::class, [
            'offer_accepted' => true,
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
            'custom_offer' => false,
            'counter_offer' => false,
        ]);
    }

    #[Test]
    public function it_can_auto_approved_counter_offer_in_installment_type_when_amount_more_then_negotiation_type(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'counter_offer' => true,
            'current_balance' => 100,
            'pay_setup_discount_percent' => 30,
            'min_monthly_pay_percent' => 20,
            'max_days_first_pay' => 20,
        ]);

        $consumerNegotiation = ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
            'active_negotiation' => true,
            'offer_accepted' => false,
            'counter_offer_accepted' => false,
            'negotiation_type' => NegotiationType::INSTALLMENT,
            'counter_one_time_amount' => 100,
            'counter_monthly_amount' => 20,
            'counter_negotiate_amount' => 100,
            'counter_first_pay_date' => $date = today()->addDay()->toDateString(),
            'monthly_amount' => 10,
            'negotiate_amount' => 100,
            'first_pay_date' => $date,
        ]);

        Livewire::test(ViewOffer::class, [
            'consumer' => $this->consumer,
            'view' => fake()->randomElement(['card', 'grid']),
        ])
            ->set('form.counter_first_pay_date', today()->addDays(2)->toDateString())
            ->set('form.monthly_amount', 2000000)
            ->set('form.counter_note', $note = fake()->word())
            ->call('submitCounterOffer')
            ->assertHasNoErrors()
            ->assertOk()
            ->assertDispatched('close-dialog-of-counter-offer');

        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'id' => $consumerNegotiation->id,
            'counter_one_time_amount' => null,
            'counter_monthly_amount' => null,
            'counter_negotiate_amount' => null,
            'counter_first_pay_date' => null,
            'counter_no_of_installments' => null,
            'monthly_amount' => 70,
            'negotiate_amount' => $consumerNegotiation->negotiate_amount,
            'first_pay_date' => today()->addDays(2)->format('Y-m-d H:i:s'),
            'note' => $note,
            'no_of_installments' => 1,
        ]);

        $this->assertDatabaseHas(Consumer::class, [
            'offer_accepted' => true,
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
            'custom_offer' => false,
            'counter_offer' => false,
        ]);
    }

    #[Test]
    public function it_can_auto_approved_counter_offer_on_pif(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'counter_offer' => true,
            'current_balance' => 100,
            'pif_discount_percent' => 30,
            'max_days_first_pay' => 20,
        ]);

        $consumerNegotiation = ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
            'active_negotiation' => true,
            'offer_accepted' => false,
            'counter_offer_accepted' => false,
            'negotiation_type' => NegotiationType::PIF,
            'counter_one_time_amount' => 100,
            'counter_monthly_amount' => 20,
            'counter_negotiate_amount' => 100,
            'counter_first_pay_date' => $date = today()->addDay()->toDateString(),
            'monthly_amount' => null,
            'negotiate_amount' => null,
            'first_pay_date' => $date,
        ]);

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->set('form.counter_first_pay_date', today()->addDays(2)->toDateString())
            ->set('form.monthly_amount', 900)
            ->set('form.counter_note', $note = fake()->word())
            ->call('submitCounterOffer')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'id' => $consumerNegotiation->id,
            'counter_one_time_amount' => null,
            'counter_monthly_amount' => null,
            'counter_negotiate_amount' => null,
            'counter_first_pay_date' => null,
            'counter_no_of_installments' => null,
            'monthly_amount' => null,
            'negotiate_amount' => null,
            'first_pay_date' => today()->addDays(2)->format('Y-m-d H:i:s'),
            'note' => $note,
            'no_of_installments' => null,
            'one_time_settlement' => 70,
        ]);

        $this->assertDatabaseHas(Consumer::class, [
            'offer_accepted' => true,
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
            'custom_offer' => false,
            'counter_offer' => false,
        ]);
    }

    #[Test]
    public function it_can_auto_setup_counter_offer_on_pif(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'counter_offer' => true,
            'current_balance' => 100,
            'pif_discount_percent' => 30,
            'max_days_first_pay' => 20,
        ]);

        $consumerNegotiation = ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
            'active_negotiation' => true,
            'offer_accepted' => false,
            'counter_offer_accepted' => false,
            'negotiation_type' => NegotiationType::PIF,
            'counter_one_time_amount' => 100,
            'counter_monthly_amount' => 20,
            'counter_negotiate_amount' => 100,
            'counter_first_pay_date' => $date = today()->addDay()->toDateString(),
            'monthly_amount' => null,
            'negotiate_amount' => null,
            'first_pay_date' => $date,
        ]);

        Livewire::test(ViewOffer::class, ['consumer' => $this->consumer])
            ->set('form.counter_first_pay_date', today()->addDays(2)->toDateString())
            ->set('form.monthly_amount', $amount = 50)
            ->set('form.counter_note', $note = fake()->word())
            ->call('submitCounterOffer')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'id' => $consumerNegotiation->id,
            'counter_one_time_amount' => null,
            'counter_monthly_amount' => null,
            'counter_negotiate_amount' => null,
            'counter_first_pay_date' => null,
            'counter_no_of_installments' => null,
            'monthly_amount' => null,
            'negotiate_amount' => null,
            'first_pay_date' => today()->addDays(2)->format('Y-m-d H:i:s'),
            'note' => $note,
            'no_of_installments' => null,
            'one_time_settlement' => $amount,
        ]);

        $this->assertDatabaseHas(Consumer::class, [
            'offer_accepted' => false,
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'custom_offer' => true,
            'counter_offer' => false,
        ]);
    }
}
