<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use AllowDynamicProperties;
use App\Enums\BankAccountType;
use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\MerchantName;
use App\Enums\MerchantType;
use App\Enums\NegotiationType;
use App\Enums\State;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Consumer\ExternalPayment;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ExternalPaymentProfile;
use App\Models\Membership;
use App\Models\Merchant;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use App\Services\Consumer\AuthorizePaymentService;
use App\Services\Consumer\StripePaymentService;
use App\Services\Consumer\USAEpayPaymentService;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class ExternalPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->has(ConsumerNegotiation::factory()->state(['negotiation_type' => NegotiationType::INSTALLMENT]))
            ->has(
                ScheduleTransaction::factory()
                    ->state([
                        'status' => TransactionStatus::SCHEDULED,
                        'external_payment_profile_id' => null,
                        'amount' => 23.44,
                    ]),
                'scheduledTransactions'
            )
            ->create(['status' => ConsumerStatus::PAYMENT_ACCEPTED]);
    }

    #[Test]
    public function it_can_not_render_livewire_component(): void
    {
        $url = URL::temporarySignedRoute(
            'consumer.external-payment',
            now()->addHour(),
            ['c' => bin2hex((string) $this->consumer->id)]
        );

        $this->withoutVite()
            ->get($url)
            ->assertSeeLivewire(ExternalPayment::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertViewIs('livewire.consumer.external-payment')
            ->assertViewHas('consumer.id', $this->consumer->id)
            ->assertViewHas('merchants', fn (Collection $merchants) => $merchants->isEmpty());
    }

    #[Test]
    public function it_can_render_livewire_component_with_some_data(): void
    {
        $this->consumer->update(['payment_setup' => true]);
        $this->consumer->subclient()->update(['has_merchant' => true]);

        $merchant = Merchant::factory()
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create();

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertViewIs('livewire.consumer.external-payment')
            ->assertViewHas('merchants', fn (Collection $merchants) => $merchant->is($merchants->first()))
            ->assertViewHas('consumer.id', $this->consumer->id)
            ->assertSet('totalPayableAmount', $this->consumer->scheduledTransactions->sum('amount'))
            ->assertSet('amount', '')
            ->assertSet('paymentIsSuccessful', false);
    }

    #[Test]
    public function it_can_throw_validation_error_for_amount_is_required(): void
    {
        $this->consumer->update(['payment_setup' => true]);

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertViewIs('livewire.consumer.external-payment')
            ->assertSet('paymentIsSuccessful', false)
            ->assertSet('amount', '')
            ->call('makePayment')
            ->assertOk()
            ->assertHasErrors(['amount' => ['required']])
            ->assertSee(__('validation.required', ['attribute' => 'amount']));
    }

    #[Test]
    public function it_can_throw_validation_error_if_amount_is_greater_than_total_payable(): void
    {
        $this->consumer->update(['payment_setup' => true]);
        $this->consumer->scheduledTransactions->toQuery()->update(['amount' => 10.54]);

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertViewIs('livewire.consumer.external-payment')
            ->assertSet('paymentIsSuccessful', false)
            ->set('amount', '12.24')
            ->set('form.first_name', fake()->firstName())
            ->set('form.last_name', fake()->lastName())
            ->set('form.method', MerchantType::CC->value)
            ->set('form.card_holder_name', fake()->name())
            ->set('form.card_number', '4242424242424242')
            ->set('form.cvv', '123')
            ->set('form.expiry', today()->addYear()->format('m/Y'))
            ->set('form.address', fake()->streetAddress())
            ->set('form.city', fake()->city())
            ->set('form.state', fake()->randomElement(State::values()))
            ->set('form.zip', fake()->randomNumber(5, strict: true))
            ->set('form.is_terms_accepted', true)
            ->call('makePayment')
            ->assertOk()
            ->assertHasErrors(['amount' => [__('validation.lte.numeric', ['attribute' => 'amount', 'value' => 10.54])]]);
    }

    #[Test]
    public function it_can_donate_by_authorize_merchant(): void
    {
        Queue::fake();

        $this->consumer->update(['payment_setup' => true]);
        $this->consumer->subclient()->update(['has_merchant' => true]);

        Merchant::factory()
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'merchant_name' => MerchantName::AUTHORIZE,
                'merchant_type' => MerchantType::CC,
            ]);

        $this->consumer->scheduledTransactions()->update(['amount' => 10.54]);

        $transactionId = fake()->uuid();

        $this->partialMock(AuthorizePaymentService::class, function (MockInterface $mock) use ($transactionId): void {
            $mock->shouldReceive('makePayment')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($transactionId);
        });

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertViewIs('livewire.consumer.external-payment')
            ->set('amount', '9.32')
            ->set('form.first_name', $firstName = fake()->firstName())
            ->set('form.last_name', $lastName = fake()->lastName())
            ->set('form.method', MerchantType::CC->value)
            ->set('form.card_holder_name', fake()->name())
            ->set('form.card_number', '4242424242424242')
            ->set('form.expiry', $expiry = today()->addYear()->format('m/Y'))
            ->set('form.cvv', '124')
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->randomNumber(5, strict: true))
            ->set('form.is_terms_accepted', true)
            ->call('makePayment')
            ->assertOk()
            ->assertSet('paymentIsSuccessful', true);

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::HELPING_HAND_SUCCESSFUL_PAYMENT
        );

        $externalPaymentProfile = ExternalPaymentProfile::query()->firstOrFail();

        $scheduledTransaction = $this->consumer->scheduledTransactions()->first();

        $this->assertNull($scheduledTransaction->transaction_id);
        $this->assertEquals($externalPaymentProfile->id, $scheduledTransaction->external_payment_profile_id);
        $this->assertEquals('1.22', $scheduledTransaction->amount);

        $this->assertEquals($this->consumer->company_id, $externalPaymentProfile->company_id);
        $this->assertEquals($this->consumer->subclient_id, $externalPaymentProfile->subclient_id);
        $this->assertEquals($this->consumer->id, $externalPaymentProfile->consumer_id);
        $this->assertEquals($firstName, $externalPaymentProfile->first_name);
        $this->assertEquals($lastName, $externalPaymentProfile->last_name);
        $this->assertEquals($address, $externalPaymentProfile->address);
        $this->assertEquals($state, $externalPaymentProfile->state);
        $this->assertEquals($city, $externalPaymentProfile->city);
        $this->assertEquals((string) $zip, $externalPaymentProfile->zip);
        $this->assertEquals('4242', $externalPaymentProfile->last_four_digit);
        $this->assertEquals($expiry, $externalPaymentProfile->expiry);
        $this->assertNull($externalPaymentProfile->account_number);
        $this->assertNull($externalPaymentProfile->routing_number);
    }

    #[Test]
    public function it_can_donate_by_authorize_merchant_when_payment_setup_is_pending(): void
    {
        Queue::fake();

        $this->consumer->update(['payment_setup' => false]);
        $this->consumer->subclient()->update(['has_merchant' => true]);

        Merchant::factory()
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'merchant_name' => MerchantName::AUTHORIZE,
                'merchant_type' => MerchantType::CC,
            ]);

        $this->consumer->scheduledTransactions()->update(['amount' => 10.54]);

        $transactionId = fake()->uuid();

        $this->partialMock(AuthorizePaymentService::class, function (MockInterface $mock) use ($transactionId): void {
            $mock->shouldReceive('makePayment')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($transactionId);
        });

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertViewIs('livewire.consumer.external-payment')
            ->set('amount', '9.32')
            ->set('form.first_name', $firstName = fake()->firstName())
            ->set('form.last_name', $lastName = fake()->lastName())
            ->set('form.method', MerchantType::CC->value)
            ->set('form.card_holder_name', fake()->name())
            ->set('form.card_number', '4242424242424242')
            ->set('form.expiry', $expiry = today()->addYear()->format('m/Y'))
            ->set('form.cvv', '124')
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->randomNumber(5, strict: true))
            ->set('form.is_terms_accepted', true)
            ->call('makePayment')
            ->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::HELPING_HAND_FULL_PAY_SETTLED
        );

        $externalPaymentProfile = ExternalPaymentProfile::query()->firstOrFail();

        $this->assertEmpty($this->consumer->scheduledTransactions);

        $this->assertEquals($this->consumer->company_id, $externalPaymentProfile->company_id);
        $this->assertEquals($this->consumer->subclient_id, $externalPaymentProfile->subclient_id);
        $this->assertEquals($this->consumer->id, $externalPaymentProfile->consumer_id);
        $this->assertEquals($firstName, $externalPaymentProfile->first_name);
        $this->assertEquals($lastName, $externalPaymentProfile->last_name);
        $this->assertEquals($address, $externalPaymentProfile->address);
        $this->assertEquals($state, $externalPaymentProfile->state);
        $this->assertEquals($city, $externalPaymentProfile->city);
        $this->assertEquals((string) $zip, $externalPaymentProfile->zip);
        $this->assertEquals('4242', $externalPaymentProfile->last_four_digit);
        $this->assertEquals($expiry, $externalPaymentProfile->expiry);
        $this->assertNull($externalPaymentProfile->account_number);
        $this->assertNull($externalPaymentProfile->routing_number);
    }

    #[Test]
    public function it_can_donate_to_consumer_by_usaepay_merchant(): void
    {
        Queue::fake();

        $this->consumer->update(['payment_setup' => true]);
        $this->consumer->subclient()->update(['has_merchant' => true]);

        Merchant::factory()
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'merchant_name' => MerchantName::USA_EPAY,
                'merchant_type' => MerchantType::ACH,
            ]);

        $this->consumer->scheduledTransactions()->update([
            'amount' => 10.54,
            'schedule_date' => today()->subDay()->toDateString(),
            'transaction_id' => null,
        ]);

        ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'amount' => $sequence->index === 4 ? 12.53 : 10.54,
                'schedule_date' => today()->addDays($sequence->index)->toDateString(),
            ])
            ->for($this->consumer)
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'external_payment_profile_id' => null,
                'transaction_id' => null,
            ]);

        $transactionId = fake()->uuid();

        $this->partialMock(USAEpayPaymentService::class, function (MockInterface $mock) use ($transactionId): void {
            $mock->shouldReceive('makePayment')
                ->withAnyArgs()
                ->atLeast()
                ->once()
                ->andReturn($transactionId);
        });

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertSet('consumer.id', $this->consumer->id)
            ->assertSet('paymentIsSuccessful', false)
            ->assertViewIs('livewire.consumer.external-payment')
            ->set('amount', '43.32')
            ->set('form.first_name', $firstName = fake()->firstName())
            ->set('form.last_name', $lastName = fake()->lastName())
            ->set('form.method', MerchantType::ACH->value)
            ->set('form.account_number', '13545482')
            ->set('form.account_type', BankAccountType::CHECKING->value)
            ->set('form.routing_number', $routingNumber = '021000021')
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->randomNumber(5, strict: true))
            ->set('form.is_terms_accepted', true)
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSet('paymentIsSuccessful', true);

        $scheduledTransactions = $this->consumer->scheduledTransactions()->orderBy('schedule_date')->get();

        $externalPaymentProfile = ExternalPaymentProfile::query()->firstOrFail();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::HELPING_HAND_SUCCESSFUL_PAYMENT
        );

        foreach ([0, 1, 2, 3] as $index) {
            $this->assertEquals(TransactionStatus::SUCCESSFUL, $scheduledTransactions->get($index)->status);
            $this->assertNotNull($scheduledTransactions->get($index)->transaction_id);
            $this->assertEquals($externalPaymentProfile->id, $scheduledTransactions->get($index)->external_payment_profile_id);
        }

        $this->assertEquals('9.38', $scheduledTransactions->get(4)->amount);
        $this->assertEquals(TransactionStatus::SCHEDULED, $scheduledTransactions->get(4)->status);
        $this->assertNull($scheduledTransactions->get(4)->transaction_id);
        $this->assertEquals($externalPaymentProfile->id, $scheduledTransactions->get(4)->external_payment_profile_id);

        $this->assertEquals('12.53', $scheduledTransactions->last()->amount);
        $this->assertEquals(TransactionStatus::SCHEDULED, $scheduledTransactions->last()->status);
        $this->assertNull($scheduledTransactions->last()->transaction_id);
        $this->assertNull($scheduledTransactions->last()->external_payment_profile_id);

        $this->assertEquals($this->consumer->company_id, $externalPaymentProfile->company_id);
        $this->assertEquals($this->consumer->subclient_id, $externalPaymentProfile->subclient_id);
        $this->assertEquals($this->consumer->id, $externalPaymentProfile->consumer_id);
        $this->assertEquals(MerchantType::ACH, $externalPaymentProfile->method);
        $this->assertEquals($firstName, $externalPaymentProfile->first_name);
        $this->assertEquals($lastName, $externalPaymentProfile->last_name);
        $this->assertEquals($address, $externalPaymentProfile->address);
        $this->assertEquals($state, $externalPaymentProfile->state);
        $this->assertEquals($city, $externalPaymentProfile->city);
        $this->assertEquals((string) $zip, $externalPaymentProfile->zip);
        $this->assertNull($externalPaymentProfile->last_four_digit);
        $this->assertNull($externalPaymentProfile->expiry);
        $this->assertEquals('82', $externalPaymentProfile->account_number);
        $this->assertEquals((string) $routingNumber, $externalPaymentProfile->routing_number);
    }

    #[Test]
    public function it_can_donate_to_consumer_by_usaepay_merchant_when_payment_setup_is_pending(): void
    {
        Queue::fake();

        $this->consumer->update(['payment_setup' => false]);
        $this->consumer->subclient()->update(['has_merchant' => true]);

        Merchant::factory()
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'merchant_name' => MerchantName::USA_EPAY,
                'merchant_type' => MerchantType::ACH,
            ]);

        $this->consumer->scheduledTransactions()->update([
            'amount' => 10.54,
            'schedule_date' => today()->subDay()->toDateString(),
            'transaction_id' => null,
        ]);

        ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'amount' => $sequence->index === 4 ? 12.53 : 10.54,
                'schedule_date' => today()->addDays($sequence->index)->toDateString(),
            ])
            ->for($this->consumer)
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'external_payment_profile_id' => null,
                'transaction_id' => null,
            ]);

        $transactionId = fake()->uuid();

        $this->partialMock(USAEpayPaymentService::class, function (MockInterface $mock) use ($transactionId): void {
            $mock->shouldReceive('makePayment')
                ->withAnyArgs()
                ->atLeast()
                ->once()
                ->andReturn($transactionId);
        });

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertSet('consumer.id', $this->consumer->id)
            ->assertSet('paymentIsSuccessful', false)
            ->assertViewIs('livewire.consumer.external-payment')
            ->set('amount', '43.32')
            ->set('form.first_name', $firstName = fake()->firstName())
            ->set('form.last_name', $lastName = fake()->lastName())
            ->set('form.method', MerchantType::ACH->value)
            ->set('form.account_number', '13545482')
            ->set('form.account_type', BankAccountType::CHECKING->value)
            ->set('form.routing_number', $routingNumber = '021000021')
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->randomNumber(5, strict: true))
            ->set('form.is_terms_accepted', true)
            ->call('makePayment')
            ->assertHasNoErrors();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::HELPING_HAND_FULL_PAY_SETTLED
        );

        $externalPaymentProfile = ExternalPaymentProfile::query()->firstOrFail();

        $this->assertEmpty($this->consumer->scheduledTransactions);
        $this->assertEquals($this->consumer->company_id, $externalPaymentProfile->company_id);
        $this->assertEquals($this->consumer->subclient_id, $externalPaymentProfile->subclient_id);
        $this->assertEquals($this->consumer->id, $externalPaymentProfile->consumer_id);
        $this->assertEquals(MerchantType::ACH, $externalPaymentProfile->method);
        $this->assertEquals($firstName, $externalPaymentProfile->first_name);
        $this->assertEquals($lastName, $externalPaymentProfile->last_name);
        $this->assertEquals($address, $externalPaymentProfile->address);
        $this->assertEquals($state, $externalPaymentProfile->state);
        $this->assertEquals($city, $externalPaymentProfile->city);
        $this->assertEquals((string) $zip, $externalPaymentProfile->zip);
        $this->assertNull($externalPaymentProfile->last_four_digit);
        $this->assertNull($externalPaymentProfile->expiry);
        $this->assertEquals('82', $externalPaymentProfile->account_number);
        $this->assertEquals((string) $routingNumber, $externalPaymentProfile->routing_number);
    }

    #[Test]
    public function it_can_donate_to_consumer_by_stripe_merchant(): void
    {
        $this->consumer->update(['payment_setup' => false]);

        Queue::fake();

        $this->consumer->subclient()->update(['has_merchant' => true]);

        Merchant::factory()
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create(['merchant_name' => MerchantName::STRIPE]);

        $this->consumer->scheduledTransactions()->update(['amount' => '2957']);

        $this->consumer->consumerProfile()->update(['email_permission' => true]);

        $transactionId = fake()->uuid();

        $this->partialMock(StripePaymentService::class, function (MockInterface $mock) use ($transactionId): void {
            $mock->shouldReceive('makePayment')
                ->withAnyArgs()
                ->atLeast()
                ->once()
                ->andReturn($transactionId);
        });

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertSet('consumer.id', $this->consumer->id)
            ->assertSet('paymentIsSuccessful', false)
            ->assertViewIs('livewire.consumer.external-payment')
            ->set('amount', '2957')
            ->set('form.first_name', $firstName = fake()->firstName())
            ->set('form.last_name', $lastName = fake()->lastName())
            ->set('form.method', MerchantType::CC->value)
            ->set('form.card_holder_name', fake()->name())
            ->set('form.card_number', '4242424242424242')
            ->set('form.expiry', $expiry = today()->addYear()->format('m/Y'))
            ->set('form.cvv', '124')
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->randomNumber(5, strict: true))
            ->set('form.is_terms_accepted', true)
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertOk();

        $externalPaymentProfile = ExternalPaymentProfile::query()->firstOrFail();

        $this->assertNotEquals(0.0, $this->consumer->refresh()->current_balance);
        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->status);
        $this->assertFalse($this->consumer->has_failed_payment);
        $this->assertEquals($this->consumer->company_id, $externalPaymentProfile->company_id);
        $this->assertEquals($this->consumer->subclient_id, $externalPaymentProfile->subclient_id);
        $this->assertEquals($this->consumer->id, $externalPaymentProfile->consumer_id);
        $this->assertEquals($firstName, $externalPaymentProfile->first_name);
        $this->assertEquals($lastName, $externalPaymentProfile->last_name);
        $this->assertEquals($address, $externalPaymentProfile->address);
        $this->assertEquals($state, $externalPaymentProfile->state);
        $this->assertEquals($city, $externalPaymentProfile->city);
        $this->assertEquals((string) $zip, $externalPaymentProfile->zip);
        $this->assertEquals('4242', $externalPaymentProfile->last_four_digit);
        $this->assertEquals($expiry, $externalPaymentProfile->expiry);
        $this->assertNull($externalPaymentProfile->account_number);
        $this->assertNull($externalPaymentProfile->routing_number);

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::HELPING_HAND_FULL_PAY_SETTLED
        );
    }

    #[Test]
    public function it_can_donate_to_consumer_by_tilled_merchant(): void
    {
        Queue::fake();

        $this->consumer->update([
            'payment_setup' => false,
            'current_balance' => $consumerAmount = 1000,
            'pif_discount_percent' => $pifDiscount = fake()->numberBetween(1, 50),
        ]);

        $paidAmount = $consumerAmount - ($consumerAmount * $pifDiscount / 100);

        $this->consumer->subclient()->update(['has_merchant' => true]);

        CompanyMembership::factory()
            ->for(Membership::factory()->create(['fee' => $fee = fake()->numberBetween(0, 50)]))
            ->for($this->consumer->company)
            ->create();

        Merchant::factory()
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'merchant_name' => MerchantName::YOU_NEGOTIATE,
                'merchant_type' => MerchantType::ACH,
            ]);

        $transactionId = fake()->uuid();

        Http::fake(fn () => Http::response([
            'status' => 'processing',
            'id' => $transactionId,
        ]));

        $this->consumer->scheduledTransactions()->update(['amount' => 55.32]);

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->set('amount', $amount = 47.34)
            ->set('form.first_name', $firstName = fake()->firstName())
            ->set('form.last_name', $lastName = fake()->lastName())
            ->set('form.method', MerchantType::ACH->value)
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->randomNumber(5, strict: true))
            ->set('form.is_terms_accepted', true)
            ->set('form.is_pif', false)
            ->set('form.payment_method_id', fake()->uuid())
            ->set('form.tilled_response', [
                'ach_debit' => [
                    'last2' => '02',
                    'routing_number' => '021000021',
                ],
            ])
            ->call('makePayment')
            ->assertHasNoErrors();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::HELPING_HAND_FULL_PAY_SETTLED
        );

        $externalPaymentProfile = ExternalPaymentProfile::query()->firstOrFail();
        $transaction = Transaction::query()->firstOrFail();

        $this->assertEquals($this->consumer->company_id, $externalPaymentProfile->company_id);
        $this->assertEquals($this->consumer->subclient_id, $externalPaymentProfile->subclient_id);
        $this->assertEquals($this->consumer->id, $externalPaymentProfile->consumer_id);
        $this->assertEquals(MerchantType::ACH, $externalPaymentProfile->method);
        $this->assertEquals($firstName, $externalPaymentProfile->first_name);
        $this->assertEquals($lastName, $externalPaymentProfile->last_name);
        $this->assertEquals($address, $externalPaymentProfile->address);
        $this->assertEquals($state, $externalPaymentProfile->state);
        $this->assertEquals($city, $externalPaymentProfile->city);
        $this->assertEquals((string) $zip, $externalPaymentProfile->zip);
        $this->assertNull($externalPaymentProfile->last_four_digit);
        $this->assertNull($externalPaymentProfile->expiry);
        $this->assertEquals('02', $externalPaymentProfile->account_number);
        $this->assertEquals('021000021', $externalPaymentProfile->routing_number);

        $this->assertEquals((string) $this->consumer->id, $transaction->consumer_id);
        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);
        $this->assertEquals((string) $this->consumer->company_id, $transaction->company_id);
        $this->assertEquals($this->consumer->subclient_id, $transaction->subclient_id);
        $this->assertEquals($externalPaymentProfile->id, $transaction->external_payment_profile_id);
        $this->assertEquals($transactionId, $transaction->transaction_id);
        $this->assertEquals(TransactionType::PARTIAL_PIF, $transaction->transaction_type);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $transaction->status);
        $this->assertEquals((string) Number::format($paidAmount, 2), $transaction->amount);

        $ynShare = number_format($paidAmount * $fee / 100, 2, thousands_separator: '');
        $companyShare = number_format($paidAmount - $ynShare, 2, thousands_separator: '');

        $this->assertEquals($ynShare, $transaction->rnn_share);
        $this->assertEquals($companyShare, $transaction->company_share);
        $this->assertEquals($fee, $transaction->revenue_share_percentage);
        $this->assertEquals($transactionId, $transaction->gateway_response['id']);
        $this->assertEquals('processing', $transaction->gateway_response['status']);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $transaction->status);
        $this->assertEquals($transactionId, $transaction->transaction_id);
        $this->assertNotNull($transaction->rnn_share);
        $this->assertNotNull($transaction->company_share);
        $this->assertEquals(9001, $transaction->rnn_invoice_id);
        $this->assertEquals(MerchantType::ACH->value, $transaction->payment_mode);
        $this->assertFalse($transaction->superadmin_process);
    }

    #[Test]
    public function it_can_donate_to_consumer_by_tilled_merchant_when_payment_setup_is_pending(): void
    {
        Queue::fake();

        $this->consumer->subclient()->update(['has_merchant' => true]);
        $this->consumer->update([
            'payment_setup' => false,
            'current_balance' => $consumerAmount = 1000,
            'pif_discount_percent' => $pifDiscount = fake()->numberBetween(1, 50),
        ]);

        $paidAmount = $consumerAmount - ($consumerAmount * $pifDiscount / 100);

        CompanyMembership::factory()
            ->for(Membership::factory()->create(['fee' => $fee = fake()->numberBetween(0, 50)]))
            ->for($this->consumer->company)
            ->create();

        Merchant::factory()
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'merchant_name' => MerchantName::YOU_NEGOTIATE,
                'merchant_type' => MerchantType::ACH,
            ]);

        $transactionId = fake()->uuid();

        Http::fake(fn () => Http::response([
            'status' => 'processing',
            'id' => $transactionId,
        ]));

        $this->consumer->scheduledTransactions()->update(['amount' => 55.32]);

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->set('amount', 55.32)
            ->set('form.first_name', $firstName = fake()->firstName())
            ->set('form.last_name', $lastName = fake()->lastName())
            ->set('form.method', MerchantType::ACH->value)
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->randomNumber(5, strict: true))
            ->set('form.is_terms_accepted', true)
            ->set('form.payment_method_id', fake()->uuid())
            ->set('form.tilled_response', [
                'ach_debit' => [
                    'last2' => '02',
                    'routing_number' => '021000021',
                ],
            ])
            ->set('form.is_pif', false)
            ->call('makePayment')
            ->assertHasNoErrors();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::HELPING_HAND_FULL_PAY_SETTLED
        );

        $externalPaymentProfile = ExternalPaymentProfile::query()->firstOrFail();
        $transaction = Transaction::query()->firstOrFail();

        $this->assertEquals($this->consumer->company_id, $externalPaymentProfile->company_id);
        $this->assertEquals($this->consumer->subclient_id, $externalPaymentProfile->subclient_id);
        $this->assertEquals($this->consumer->id, $externalPaymentProfile->consumer_id);
        $this->assertEquals(MerchantType::ACH, $externalPaymentProfile->method);
        $this->assertEquals($firstName, $externalPaymentProfile->first_name);
        $this->assertEquals($lastName, $externalPaymentProfile->last_name);
        $this->assertEquals($address, $externalPaymentProfile->address);
        $this->assertEquals($state, $externalPaymentProfile->state);
        $this->assertEquals($city, $externalPaymentProfile->city);
        $this->assertEquals((string) $zip, $externalPaymentProfile->zip);
        $this->assertNull($externalPaymentProfile->last_four_digit);
        $this->assertNull($externalPaymentProfile->expiry);
        $this->assertEquals('02', $externalPaymentProfile->account_number);
        $this->assertEquals('021000021', $externalPaymentProfile->routing_number);

        $this->assertEquals((string) $this->consumer->id, $transaction->consumer_id);
        $this->assertEquals((string) $this->consumer->company_id, $transaction->company_id);
        $this->assertEquals($this->consumer->subclient_id, $transaction->subclient_id);
        $this->assertEquals($externalPaymentProfile->id, $transaction->external_payment_profile_id);
        $this->assertEquals($transactionId, $transaction->transaction_id);
        $this->assertEquals(TransactionType::PARTIAL_PIF, $transaction->transaction_type);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $transaction->status);
        $this->assertEquals((string) Number::format($paidAmount, 2), $transaction->amount);

        $ynShare = number_format($paidAmount * $fee / 100, 2, thousands_separator: '');
        $companyShare = number_format($paidAmount - $ynShare, 2, thousands_separator: '');

        $this->assertEquals($ynShare, $transaction->rnn_share);
        $this->assertEquals($companyShare, $transaction->company_share);
        $this->assertEquals($fee, $transaction->revenue_share_percentage);
        $this->assertEquals($transactionId, $transaction->gateway_response['id']);
        $this->assertEquals('processing', $transaction->gateway_response['status']);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $transaction->status);
        $this->assertEquals($transactionId, $transaction->transaction_id);
        $this->assertNotNull($transaction->rnn_share);
        $this->assertNotNull($transaction->company_share);
        $this->assertEquals(9001, $transaction->rnn_invoice_id);
        $this->assertEquals(MerchantType::ACH->value, $transaction->payment_mode);
        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);
        $this->assertFalse($transaction->superadmin_process);
    }

    #[Test]
    public function it_can_download_receipt(): void
    {
        $externalPaymentProfile = ExternalPaymentProfile::factory()
            ->has(
                ScheduleTransaction::factory()
                    ->for($this->consumer->company)
                    ->for($this->consumer)
                    ->state([
                        'subclient_id' => null,
                        'status' => TransactionStatus::SUCCESSFUL,
                    ])
            )
            ->has(
                Transaction::factory()
                    ->for($this->consumer->company)
                    ->for($this->consumer)
                    ->state([
                        'subclient_id' => null,
                        'status' => TransactionStatus::SUCCESSFUL,
                    ])
            )
            ->create();

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->set('externalPaymentProfile', $externalPaymentProfile)
            ->assertSet('paymentIsSuccessful', false)
            ->call('downloadReceipt')
            ->assertOk()
            ->assertDispatched('dont-close-dialog')
            ->assertFileDownloaded('you_negotiate_receipt.pdf');
    }

    #[Test]
    public function it_can_donate_to_full_payment_of_all_schedule_transactions_consumer(): void
    {
        $this->consumer->subclient()->update(['has_merchant' => true]);

        Queue::fake();

        Merchant::factory()
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'merchant_name' => MerchantName::USA_EPAY,
                'merchant_type' => MerchantType::ACH,
            ]);

        ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'schedule_date' => today()->addDays($sequence->index)->toDateString(),
            ])
            ->for($this->consumer)
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'amount' => 10,
                'status' => TransactionStatus::SCHEDULED,
                'external_payment_profile_id' => null,
                'transaction_id' => null,
            ]);

        $transactionId = fake()->uuid();

        $this->partialMock(USAEpayPaymentService::class, function (MockInterface $mock) use ($transactionId): void {
            $mock->shouldReceive('makePayment')
                ->withAnyArgs()
                ->atLeast()
                ->once()
                ->andReturn($transactionId);
        });

        Livewire::withQueryParams(['c' => bin2hex((string) $this->consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertSet('consumer.id', $this->consumer->id)
            ->assertSet('paymentIsSuccessful', false)
            ->assertViewIs('livewire.consumer.external-payment')
            ->set('amount', 73.44)
            ->set('form.first_name', fake()->firstName())
            ->set('form.last_name', fake()->lastName())
            ->set('form.method', MerchantType::ACH->value)
            ->set('form.account_number', '13545482')
            ->set('form.account_type', BankAccountType::CHECKING->value)
            ->set('form.routing_number', '021000021')
            ->set('form.address', fake()->streetAddress())
            ->set('form.city', fake()->city())
            ->set('form.state', fake()->randomElement(State::values()))
            ->set('form.zip', fake()->randomNumber(5, strict: true))
            ->set('form.is_terms_accepted', true)
            ->call('makePayment')
            ->assertHasNoErrors();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) !== CommunicationCode::HELPING_HAND_SUCCESSFUL_PAYMENT
        );

        $this->assertDatabaseMissing(ScheduleTransaction::class, ['status' => TransactionStatus::SCHEDULED->value]);
    }

    #[Test]
    public function it_can_donate_to_full_payment_when_consumer_status_is_joined(): void
    {
        Queue::fake();

        $consumer = Consumer::factory()
            ->create([
                'status' => ConsumerStatus::JOINED,
                'current_balance' => 599.00,
                'offer_accepted' => false,
                'payment_setup' => false,
            ]);

        $consumer->subclient()->update(['has_merchant' => true]);

        Merchant::factory()
            ->for($consumer->company)
            ->for($consumer->subclient)
            ->create(['merchant_name' => MerchantName::STRIPE]);

        $transactionId = fake()->uuid();

        $this->partialMock(StripePaymentService::class, function (MockInterface $mock) use ($transactionId): void {
            $mock->shouldReceive('makePayment')
                ->withAnyArgs()
                ->atLeast()
                ->once()
                ->andReturn($transactionId);
        });

        Livewire::withQueryParams(['c' => bin2hex((string) $consumer->id)])
            ->test(ExternalPayment::class)
            ->assertOk()
            ->assertSet('consumer.id', $consumer->id)
            ->assertSet('paymentIsSuccessful', false)
            ->assertViewIs('livewire.consumer.external-payment')
            ->set('amount', '599')
            ->set('form.first_name', $firstName = fake()->firstName())
            ->set('form.last_name', $lastName = fake()->lastName())
            ->set('form.method', MerchantType::CC->value)
            ->set('form.card_holder_name', fake()->name())
            ->set('form.card_number', '4242424242424242')
            ->set('form.expiry', $expiry = today()->addYear()->format('m/Y'))
            ->set('form.cvv', '124')
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->randomNumber(5, strict: true))
            ->set('form.is_terms_accepted', true)
            ->set('form.is_pif', true)
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertOk();

        $externalPaymentProfile = ExternalPaymentProfile::query()->firstOrFail();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::HELPING_HAND_FULL_PAY_SETTLED
        );

        $this->assertEquals(ConsumerStatus::SETTLED, $consumer->refresh()->status);
        $this->assertEquals($consumer->company_id, $externalPaymentProfile->company_id);
        $this->assertEquals($consumer->subclient_id, $externalPaymentProfile->subclient_id);
        $this->assertEquals($consumer->id, $externalPaymentProfile->consumer_id);
        $this->assertEquals($firstName, $externalPaymentProfile->first_name);
        $this->assertEquals($lastName, $externalPaymentProfile->last_name);
        $this->assertEquals($address, $externalPaymentProfile->address);
        $this->assertEquals($state, $externalPaymentProfile->state);
        $this->assertEquals($city, $externalPaymentProfile->city);
        $this->assertEquals((string) $zip, $externalPaymentProfile->zip);
        $this->assertEquals('4242', $externalPaymentProfile->last_four_digit);
        $this->assertEquals($expiry, $externalPaymentProfile->expiry);
        $this->assertNull($externalPaymentProfile->account_number);
        $this->assertNull($externalPaymentProfile->routing_number);
    }
}
