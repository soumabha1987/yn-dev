<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\Payment;

use AllowDynamicProperties;
use App\Enums\AutomatedCommunicationHistoryStatus;
use App\Enums\BankAccountType;
use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\MerchantName;
use App\Enums\NegotiationType;
use App\Enums\State;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Livewire\Consumer\Payment;
use App\Mail\AutomatedTemplateMail;
use App\Models\AutomatedCommunicationHistory;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ConsumerProfile;
use App\Models\Membership;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use App\Services\Consumer\AuthorizePaymentService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery\MockInterface;
use net\authorize\api\contract\v1\CreateCustomerProfileResponse;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\MessagesType;
use net\authorize\api\contract\v1\TransactionResponseType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class AuthorizePaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state([
                'email_permission' => true,
                'text_permission' => false,
            ]))
            ->create([
                'status' => ConsumerStatus::JOINED,
                'custom_offer' => false,
                'current_balance' => $this->amount = 1000,
                'pif_discount_percent' => null,
                'subclient_id' => null,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');

        $this->merchant = Merchant::factory()
            ->for($this->consumer->company)
            ->create([
                'merchant_name' => MerchantName::AUTHORIZE,
                'subclient_id' => null,
            ]);

        $this->paymentProfile = PaymentProfile::factory()
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create();

        CompanyMembership::factory()
            ->for(Membership::factory()->create(['fee' => $this->fee = fake()->numberBetween(0, 50)]))
            ->for($this->consumer->company)
            ->create();

        $automatedTemplate = AutomatedTemplate::factory()->email()->create();

        $this->communicationStatus = CommunicationStatus::factory()
            ->create([
                'automated_email_template_id' => $automatedTemplate->id,
                'automated_sms_template_id' => $automatedTemplate->id,
                'code' => CommunicationCode::BALANCE_PAID,
            ]);
    }

    #[Test]
    public function make_success_payment_when_pif_negotiation_plan(): void
    {
        Mail::fake();

        $this->consumer->company()->update([
            'pif_balance_discount_percent' => $pif = fake()->numberBetween(0, 50),
        ]);

        ConsumerNegotiation::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'consumer_id' => $this->consumer->id,
                'negotiation_type' => NegotiationType::PIF,
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'active_negotiation' => true,
                'no_of_installments' => null,
                'one_time_settlement' => $this->amount - ($this->amount * $pif / 100),
                'first_pay_date' => today()->toDateString(),
            ]);

        $this->partialMock(AuthorizePaymentService::class, function (MockInterface $mock): void {
            $paymentProfile = new CreateCustomerProfileResponse;
            $paymentProfile->setCustomerPaymentProfileIdList([$this->paymentProfileId = fake()->uuid()]);
            $paymentProfile->setCustomerProfileId($this->profileId = fake()->uuid());
            $paymentProfile->setCustomerShippingAddressIdList([$this->shippingId = fake()->uuid()]);
            $messageType = new MessagesType;
            $messageType->setResultCode('Ok');
            $paymentProfile->setMessages($messageType);

            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($paymentProfile);

            $transactionResponse = new CreateTransactionResponse;
            $transactionResponseType = new TransactionResponseType;
            $transactionResponseType->setTransId($this->transactionId = fake()->uuid());
            $transactionResponseType->setMessages(['This transaction has been approved.']);
            $transactionResponseType->setResponseCode(true);
            $transactionResponse->setTransactionResponse($transactionResponseType);
            $transactionResponse->setRefId(fake()->uuid());
            $transactionResponse->setMessages($messageType);

            $mock->shouldReceive('proceedPayment')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($transactionResponse);
        });

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->address())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = 12345)
            ->set('form.method', $paymentMethod = $this->merchant->merchant_type->value)
            ->set('form.card_number', $cardNumber = '1234567890123456')
            ->set('form.card_holder_name', fake()->name())
            ->set('form.cvv', 123)
            ->set('form.account_type', fake()->randomElement(BankAccountType::values()))
            ->set('form.account_number', $accountNumber = '1234')
            ->set('form.routing_number', $routingNumber = '021000021')
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', $expiry = now()->addYear()->format('m/Y'))
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSessionHas('complete-payment')
            ->assertSessionMissing('complete-payment-setup')
            ->assertRedirect(route('consumer.complete_payment', ['consumer' => $this->consumer->id]));

        $this->assertSoftDeleted($this->paymentProfile);

        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);
        $this->assertTrue($this->consumer->payment_setup);

        $this->assertDatabaseHas(PaymentProfile::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'method' => $paymentMethod,
            'expirity' => $expiry,
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'profile_id' => $this->profileId,
            'payment_profile_id' => $this->paymentProfileId,
            'shipping_profile_id' => $this->shippingId,
            'routing_number' => $routingNumber,
            'account_number' => Str::substr($accountNumber, -2),
            'last4digit' => Str::substr($cardNumber, -4),
        ]);

        $transaction = $this->consumer->transactions->first();

        $this->assertDatabaseHas(ScheduleTransaction::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company->id,
            'status' => TransactionStatus::SUCCESSFUL,
            'status_code' => null,
            'transaction_type' => NegotiationType::PIF,
            'transaction_id' => $transaction->id,
            'attempt_count' => 1,
        ]);

        $this->assertDatabaseHas(Transaction::class, [
            'transaction_id' => $this->transactionId,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'transaction_type' => TransactionType::PIF,
            'amount' => number_format($transactionAmount = ($this->amount - ($this->amount * $pif / 100)), 2, thousands_separator: ''),
            'rnn_share' => number_format($ynShare = $transactionAmount * $this->fee / 100, 2, thousands_separator: ''),
            'company_share' => number_format($transactionAmount - $ynShare, 2, thousands_separator: ''),
            'status' => TransactionStatus::SUCCESSFUL,
            'payment_mode' => $paymentMethod,
            'gateway_response->transId' => $this->transactionId,
        ]);

        $this->assertDatabaseHas(AutomatedCommunicationHistory::class, [
            'communication_status_id' => $this->communicationStatus->id,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'status' => AutomatedCommunicationHistoryStatus::SUCCESS,
        ]);

        Mail::assertQueued(
            AutomatedTemplateMail::class,
            fn (AutomatedTemplateMail $mail): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($mail))
        );
    }

    #[Test]
    public function make_failed_payment_profile_when_pif_negotiation_plan(): void
    {
        ConsumerNegotiation::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'consumer_id' => $this->consumer->id,
                'negotiation_type' => NegotiationType::PIF,
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'active_negotiation' => true,
                'no_of_installments' => null,
                'one_time_settlement' => $this->amount,
                'first_pay_date' => today()->toDateString(),
            ]);

        $this->partialMock(AuthorizePaymentService::class, function (MockInterface $mock): void {
            $paymentProfile = new CreateCustomerProfileResponse;
            $paymentProfile->setCustomerPaymentProfileIdList([$this->paymentProfileId = fake()->uuid()]);
            $paymentProfile->setCustomerProfileId($this->profileId = fake()->uuid());
            $paymentProfile->setCustomerShippingAddressIdList([$this->shippingId = fake()->uuid()]);
            $messageType = new MessagesType;
            $messageType->setResultCode('failed');
            $paymentProfile->setMessages($messageType);

            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($paymentProfile);
        });

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->address())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = 12345)
            ->set('form.method', $paymentMethod = $this->merchant->merchant_type->value)
            ->set('form.card_number', $cardNumber = '1234567890123456')
            ->set('form.card_holder_name', fake()->name())
            ->set('form.cvv', 123)
            ->set('form.account_type', fake()->randomElement(BankAccountType::values()))
            ->set('form.account_number', $accountNumber = '1234')
            ->set('form.routing_number', $routingNumber = '021000021')
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', $expiry = now()->addYear()->format('m/Y'))
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSessionMissing('complete-payment')
            ->assertSessionMissing('complete-payment-setup')
            ->assertOk();

        $this->assertNotSoftDeleted($this->paymentProfile);
        $this->assertNotEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);

        $this->assertDatabaseMissing(PaymentProfile::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'method' => $paymentMethod,
            'expirity' => $expiry,
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'profile_id' => $this->profileId,
            'payment_profile_id' => $this->paymentProfileId,
            'shipping_profile_id' => $this->shippingId,
            'routing_number' => $routingNumber,
            'account_number' => Str::substr($accountNumber, -2),
            'last4digit' => Str::substr($cardNumber, -4),
        ]);

        $this->assertDatabaseCount(ScheduleTransaction::class, 0);
        $this->assertDatabaseCount(Transaction::class, 0);
        $this->assertDatabaseCount(AutomatedCommunicationHistory::class, 0);
    }

    #[Test]
    public function make_failed_transaction_when_pif_negotiation_plan(): void
    {
        ConsumerNegotiation::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'consumer_id' => $this->consumer->id,
                'negotiation_type' => NegotiationType::PIF,
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'active_negotiation' => true,
                'no_of_installments' => null,
                'one_time_settlement' => $this->amount,
                'first_pay_date' => today()->toDateString(),
            ]);

        $this->partialMock(AuthorizePaymentService::class, function (MockInterface $mock): void {
            $paymentProfile = new CreateCustomerProfileResponse;
            $paymentProfile->setCustomerPaymentProfileIdList([$this->paymentProfileId = fake()->uuid()]);
            $paymentProfile->setCustomerProfileId($this->profileId = fake()->uuid());
            $paymentProfile->setCustomerShippingAddressIdList([$this->shippingId = fake()->uuid()]);
            $messageType = new MessagesType;
            $messageType->setResultCode('Ok');
            $paymentProfile->setMessages($messageType);

            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($paymentProfile);

            $transactionResponse = new CreateTransactionResponse;
            $transactionResponseType = new TransactionResponseType;
            $transactionResponseType->setTransId($this->transactionId = fake()->uuid());
            $transactionResponseType->setMessages(['This transaction has been approved.']);
            $transactionResponseType->setResponseCode(true);
            $transactionResponse->setTransactionResponse($transactionResponseType);
            $transactionResponse->setRefId(fake()->uuid());

            $transactionResponseMessage = new MessagesType;
            $transactionResponseMessage->setResultCode('failed');
            $transactionResponse->setMessages($transactionResponseMessage);

            $mock->shouldReceive('proceedPayment')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($transactionResponse);
        });

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->address())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', 12345)
            ->set('form.method', $this->merchant->merchant_type->value)
            ->set('form.card_number', $cardNumber = '1234567890123456')
            ->set('form.card_holder_name', fake()->name())
            ->set('form.cvv', 123)
            ->set('form.account_type', fake()->randomElement(BankAccountType::values()))
            ->set('form.account_number', $accountNumber = '1234')
            ->set('form.routing_number', $routingNumber = '021000021')
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', $expiry = now()->addYear()->format('m/Y'))
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSessionMissing('complete-payment')
            ->assertSessionHas('complete-payment-setup')
            ->assertOk();

        $this->assertSoftDeleted($this->paymentProfile);

        $this->assertDatabaseHas(PaymentProfile::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'method' => $this->merchant->merchant_type,
            'expirity' => $expiry,
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => '12345',
            'profile_id' => $this->profileId,
            'payment_profile_id' => $this->paymentProfileId,
            'shipping_profile_id' => $this->shippingId,
            'routing_number' => $routingNumber,
            'account_number' => Str::substr($accountNumber, -2),
            'last4digit' => Str::substr($cardNumber, -4),
        ])
            ->assertDatabaseHas(ScheduleTransaction::class, [
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'subclient_id' => $this->consumer->subclient_id,
                'status' => TransactionStatus::SCHEDULED,
                'status_code' => '111',
                'transaction_type' => TransactionType::PIF,
                'stripe_payment_detail_id' => null,
            ])
            ->assertDatabaseMissing(ScheduleTransaction::class, [
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company->id,
                'status' => TransactionStatus::SUCCESSFUL,
                'status_code' => null,
                'transaction_type' => NegotiationType::PIF,
                'transaction_id' => $this->transactionId,
                'attempt_count' => 1,
            ]);

        $this->assertDatabaseCount(Transaction::class, 0);
        $this->assertDatabaseCount(AutomatedCommunicationHistory::class, 0);
    }

    #[Test]
    public function make_success_payment_profile_when_installment_negotiation_plan(): void
    {
        $this->consumer->company()->update([
            'ppa_balance_discount_percent' => $ppaPercentage = 10,
        ]);

        $discountAmount = $this->consumer->current_balance - ($this->consumer->current_balance * $ppaPercentage / 100);

        ConsumerNegotiation::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'consumer_id' => $this->consumer->id,
                'negotiation_type' => NegotiationType::INSTALLMENT,
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'active_negotiation' => true,
                'no_of_installments' => $installmentCount = 8,
                'monthly_amount' => $monthlyAmount = (int) ($discountAmount / $installmentCount),
                'last_month_amount' => $discountAmount - ($monthlyAmount * $installmentCount),
                'negotiate_amount' => $discountAmount,
                'first_pay_date' => today()->toDateString(),
            ]);

        $this->partialMock(AuthorizePaymentService::class, function (MockInterface $mock): void {
            $paymentProfile = new CreateCustomerProfileResponse;
            $paymentProfile->setCustomerPaymentProfileIdList([$this->paymentProfileId = fake()->uuid()]);
            $paymentProfile->setCustomerProfileId($this->profileId = fake()->uuid());
            $paymentProfile->setCustomerShippingAddressIdList([$this->shippingId = fake()->uuid()]);
            $messageType = new MessagesType;
            $messageType->setResultCode('Ok');
            $paymentProfile->setMessages($messageType);

            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($paymentProfile);
        });

        $this->assertDatabaseCount(ScheduleTransaction::class, 0);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->address())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = 12345)
            ->set('form.method', $paymentMethod = $this->merchant->merchant_type->value)
            ->set('form.card_number', $cardNumber = '1234567890123456')
            ->set('form.card_holder_name', fake()->name())
            ->set('form.cvv', 123)
            ->set('form.account_type', fake()->randomElement(BankAccountType::values()))
            ->set('form.account_number', $accountNumber = '1234')
            ->set('form.routing_number', $routingNumber = '021000021')
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', $expiry = now()->addYear()->format('m/Y'))
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSessionMissing('complete-payment')
            ->assertSessionHas('complete-payment-setup')
            ->assertOk();

        $this->assertSoftDeleted($this->paymentProfile);
        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $this->consumer->refresh()->status);
        $this->assertTrue($this->consumer->payment_setup);

        $this->assertDatabaseHas(PaymentProfile::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'method' => $paymentMethod,
            'expirity' => $expiry,
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'profile_id' => $this->profileId,
            'payment_profile_id' => $this->paymentProfileId,
            'shipping_profile_id' => $this->shippingId,
            'routing_number' => $routingNumber,
            'account_number' => Str::substr($accountNumber, -2),
            'last4digit' => Str::substr($cardNumber, -4),
        ]);

        $this->assertDatabaseCount(ScheduleTransaction::class, $installmentCount + 1)
            ->assertDatabaseHas(ScheduleTransaction::class, [
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company->id,
                'status' => TransactionStatus::SCHEDULED,
                'status_code' => 111,
                'amount' => $monthlyAmount,
                'schedule_date' => now()->toDateString(),
                'transaction_type' => NegotiationType::INSTALLMENT,
                'transaction_id' => null,
                'attempt_count' => 0,
                'last_attempted_at' => null,
            ]);
    }

    #[Test]
    public function make_failed_payment_profile_when_installment_negotiation_plan(): void
    {
        $this->consumer->company()->update([
            'ppa_balance_discount_percent' => $ppaPercentage = 10,
        ]);

        $discountAmount = $this->consumer->current_balance - ($this->consumer->current_balance * $ppaPercentage / 100);

        ConsumerNegotiation::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'consumer_id' => $this->consumer->id,
                'negotiation_type' => NegotiationType::INSTALLMENT,
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'active_negotiation' => true,
                'no_of_installments' => $installmentCount = 8,
                'monthly_amount' => $monthlyAmount = (int) ($discountAmount / $installmentCount),
                'last_month_amount' => $discountAmount - ($monthlyAmount * $installmentCount),
                'negotiate_amount' => $discountAmount,
                'first_pay_date' => today()->toDateString(),
            ]);

        $this->partialMock(AuthorizePaymentService::class, function (MockInterface $mock): void {
            $paymentProfile = new CreateCustomerProfileResponse;
            $paymentProfile->setCustomerPaymentProfileIdList([$this->paymentProfileId = fake()->uuid()]);
            $paymentProfile->setCustomerProfileId($this->profileId = fake()->uuid());
            $paymentProfile->setCustomerShippingAddressIdList([$this->shippingId = fake()->uuid()]);
            $messageType = new MessagesType;
            $messageType->setResultCode('failed');
            $paymentProfile->setMessages($messageType);

            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($paymentProfile);
        });

        $this->assertDatabaseCount(ScheduleTransaction::class, 0);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->address())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = 12345)
            ->set('form.method', $paymentMethod = $this->merchant->merchant_type->value)
            ->set('form.card_number', $cardNumber = '1234567890123456')
            ->set('form.card_holder_name', fake()->name())
            ->set('form.cvv', 123)
            ->set('form.account_type', fake()->randomElement(BankAccountType::values()))
            ->set('form.account_number', $accountNumber = '1234')
            ->set('form.routing_number', $routingNumber = '021000021')
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', $expiry = now()->addYear()->format('m/Y'))
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSessionMissing('complete-payment')
            ->assertSessionMissing('complete-payment-setup')
            ->assertOk();

        $this->assertNotSoftDeleted($this->paymentProfile);

        $this->assertEquals(ConsumerStatus::JOINED, $this->consumer->refresh()->status);

        $this->assertDatabaseCount(PaymentProfile::class, 1)
            ->assertDatabaseMissing(PaymentProfile::class, [
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'method' => $paymentMethod,
                'expirity' => $expiry,
                'fname' => $this->consumer->first_name,
                'lname' => $this->consumer->last_name,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip' => $zip,
                'profile_id' => $this->profileId,
                'payment_profile_id' => $this->paymentProfileId,
                'shipping_profile_id' => $this->shippingId,
                'routing_number' => $routingNumber,
                'account_number' => substr($accountNumber, -2),
                'last4digit' => substr($cardNumber, -4),
            ]);

        $this->assertDatabaseCount(ScheduleTransaction::class, 0);
    }
}
