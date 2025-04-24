<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\Payment;

use AllowDynamicProperties;
use App\Enums\AutomatedCommunicationHistoryStatus;
use App\Enums\BankAccountType;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\ConsumerStatus;
use App\Enums\CustomContentType;
use App\Enums\MerchantName;
use App\Enums\NegotiationType;
use App\Enums\State;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Livewire\Consumer\Payment;
use App\Models\AutomatedCommunicationHistory;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ConsumerProfile;
use App\Models\CustomContent;
use App\Models\Membership;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use App\Services\Consumer\USAEpayPaymentService;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class UsaepayPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state(['email_permission' => true]))
            ->create([
                'status' => ConsumerStatus::JOINED,
                'custom_offer' => false,
                'current_balance' => $this->amount = 1000,
                'subclient_id' => null,
                'pif_discount_percent' => null,
                'offer_accepted' => true,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');

        $this->merchant = Merchant::factory()
            ->for($this->consumer->company)
            ->create([
                'merchant_name' => MerchantName::USA_EPAY,
                'subclient_id' => null,
            ]);

        $this->customContent = CustomContent::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
            ]);

        $this->paymentProfile = PaymentProfile::factory()->create([
            'company_id' => $this->consumer->company_id,
            'consumer_id' => $this->consumer->id,
            'subclient_id' => null,
        ]);

        CompanyMembership::factory()
            ->for(Membership::factory()->create(['fee' => $this->fee = fake()->numberBetween(0, 50)]))
            ->for($this->consumer->company)
            ->create(['current_plan_end' => now()]);

        $automatedTemplate = AutomatedTemplate::factory()->email()->create();

        $this->communicationStatus = CommunicationStatus::factory()
            ->create([
                'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
                'automated_email_template_id' => $automatedTemplate->id,
                'automated_sms_template_id' => $automatedTemplate->id,
                'code' => CommunicationCode::BALANCE_PAID,
            ]);
    }

    #[Test]
    public function make_success_payment_when_pif_negotiation_plan(): void
    {
        $this->partialMock(USAEpayPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($this->profileId = fake()->uuid());

            $mock->shouldReceive('proceedPayment')
                ->atLeast()
                ->once()
                ->andReturn((object) [
                    'RefNum' => $this->transactionId = fake()->uuid(),
                    'ResultCode' => 'A',
                    'Result' => 'success',
                ]);
        });

        $this->consumer->company->update([
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

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->streetAddress())
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
            ->assertOk();

        $this->assertSoftDeleted($this->paymentProfile);
        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);
        $this->assertTrue($this->consumer->payment_setup);

        $transaction = $this->consumer->transactions->first();

        $this->assertDatabaseHas(PaymentProfile::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'expirity' => $expiry,
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'last4digit' => Str::substr($cardNumber, -4),
            'routing_number' => $routingNumber,
            'account_number' => Str::substr($accountNumber, -2),
            'profile_id' => $this->profileId,
            'deleted_at' => null,
        ])->assertDatabaseHas(ScheduleTransaction::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company->id,
            'status' => TransactionStatus::SUCCESSFUL,
            'status_code' => null,
            'transaction_type' => NegotiationType::PIF,
            'transaction_id' => $transaction->id,
            'attempt_count' => 1,
        ])->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => $this->transactionId,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'amount' => number_format($transactionAmount = ($this->amount - ($this->amount * $pif / 100)), 2, thousands_separator: ''),
            'rnn_share' => number_format($ynShare = $transactionAmount * $this->fee / 100, 2, thousands_separator: ''),
            'company_share' => number_format($transactionAmount - $ynShare, 2, thousands_separator: ''),
            'transaction_type' => TransactionType::PIF,
            'status' => TransactionStatus::SUCCESSFUL,
            'payment_mode' => $paymentMethod,
            'gateway_response->RefNum' => $this->transactionId,
            'gateway_response->Result' => 'success',
            'gateway_response->ResultCode' => 'A',
        ])->assertDatabaseHas(AutomatedCommunicationHistory::class, [
            'communication_status_id' => $this->communicationStatus->id,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'status' => AutomatedCommunicationHistoryStatus::SUCCESS,
        ]);
    }

    #[Test]
    public function make_success_payment_when_pif_negotiation_plan_with_two_membership(): void
    {
        CompanyMembership::factory()
            ->for(Membership::factory()->create(['fee' => $newFee = fake()->numberBetween(0, 50)]))
            ->for($this->consumer->company)
            ->create(['current_plan_end' => now()->addMonth()]);

        $this->partialMock(USAEpayPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($this->profileId = fake()->uuid());

            $mock->shouldReceive('proceedPayment')
                ->atLeast()
                ->once()
                ->andReturn((object) [
                    'RefNum' => $this->transactionId = fake()->uuid(),
                    'ResultCode' => 'A',
                    'Result' => 'success',
                ]);
        });

        $this->consumer->company->update([
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

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->streetAddress())
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
            ->assertOk();

        $this->assertSoftDeleted($this->paymentProfile);

        $transaction = $this->consumer->transactions->first();

        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);
        $this->assertTrue($this->consumer->payment_setup);
        $this->assertDatabaseHas(PaymentProfile::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'expirity' => $expiry,
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'last4digit' => Str::substr($cardNumber, -4),
            'routing_number' => $routingNumber,
            'account_number' => Str::substr($accountNumber, -2),
            'profile_id' => $this->profileId,
            'deleted_at' => null,
        ])->assertDatabaseHas(ScheduleTransaction::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company->id,
            'status' => TransactionStatus::SUCCESSFUL,
            'status_code' => null,
            'transaction_type' => NegotiationType::PIF,
            'transaction_id' => $transaction->id,
            'attempt_count' => 1,
        ])->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => $this->transactionId,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'amount' => number_format($transactionAmount = ($this->amount - ($this->amount * $pif / 100)), 2, thousands_separator: ''),
            'rnn_share' => number_format($ynShare = $transactionAmount * $newFee / 100, 2, thousands_separator: ''),
            'company_share' => number_format($transactionAmount - $ynShare, 2, thousands_separator: ''),
            'transaction_type' => TransactionType::PIF,
            'status' => TransactionStatus::SUCCESSFUL,
            'payment_mode' => $paymentMethod,
        ])->assertDatabaseHas(AutomatedCommunicationHistory::class, [
            'communication_status_id' => $this->communicationStatus->id,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'status' => AutomatedCommunicationHistoryStatus::SUCCESS,
        ]);
    }

    #[Test]
    public function make_failed_payment_profile_when_pif_negotiation_plan(): void
    {
        $this->partialMock(USAEpayPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn();

            $mock->shouldReceive('proceedPayment')
                ->never()
                ->andReturn((object) [
                    'RefNum' => $this->transactionId = fake()->uuid(),
                    'ResultCode' => 'A',
                    'Result' => 'success',
                ]);
        });

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

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->streetAddress())
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
                'profile_id' => null,
                'payment_profile_id' => null,
                'shipping_profile_id' => null,
                'routing_number' => $routingNumber,
                'account_number' => Str::substr($accountNumber, -2),
                'last4digit' => Str::substr($cardNumber, -4),
                'deleted_at' => null,
            ])
            ->assertDatabaseCount(ScheduleTransaction::class, 0)
            ->assertDatabaseCount(Transaction::class, 0)
            ->assertDatabaseCount(AutomatedCommunicationHistory::class, 0);
    }

    #[Test]
    public function make_failed_transaction_when_pif_negotiation_plan(): void
    {
        $this->partialMock(USAEpayPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($this->profileId = fake()->uuid());

            $mock->shouldReceive('proceedPayment')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn((object) [
                    'RefNum' => $this->transactionId = fake()->uuid(),
                    'ResultCode' => 'failed',
                    'Result' => 'failed',
                ]);
        });

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

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', fake()->address())
            ->set('form.city', fake()->city())
            ->set('form.state', fake()->randomElement(State::values()))
            ->set('form.zip', 12345)
            ->set('form.payment_method', $this->merchant->merchant_type->value)
            ->set('form.card_number', '1234567890123456')
            ->set('form.card_holder_name', fake()->name())
            ->set('form.cvv', 123)
            ->set('form.account_type', fake()->randomElement(BankAccountType::values()))
            ->set('form.account_number', '1234')
            ->set('form.routing_number', '021000021')
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', now()->addYear()->format('m/Y'))
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSessionMissing('complete-payment')
            ->assertSessionMissing('complete-payment-setup')
            ->assertOk();

        $this->assertNotEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);
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
                'offer_accepted' => false,
                'counter_offer_accepted' => true,
                'active_negotiation' => true,
                'payment_plan_current_balance' => null,
                'counter_no_of_installments' => $installmentCount = 8,
                'counter_monthly_amount' => $monthlyAmount = (int) ($discountAmount / $installmentCount),
                'counter_last_month_amount' => $discountAmount - ($monthlyAmount * $installmentCount),
                'counter_negotiate_amount' => $discountAmount,
                'counter_first_pay_date' => today()->toDateString(),
            ]);

        $this->partialMock(USAEpayPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn($this->profileId = fake()->uuid());

            $mock->shouldReceive('proceedPayment')
                ->never()
                ->andReturn((object) [
                    'RefNum' => $this->transactionId = fake()->uuid(),
                    'ResultCode' => 'A',
                    'Result' => 'success',
                ]);
        });

        $this->assertDatabaseCount(ScheduleTransaction::class, 0);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->address())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = 12345)
            ->set('form.payment_method', $this->merchant->merchant_type->value)
            ->set('form.card_number', $cardNumber = '1234567890123456')
            ->set('form.card_holder_name', fake()->realTextBetween(200, 250))
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
            'expirity' => $expiry,
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'deleted_at' => null,
            'profile_id' => $this->profileId,
            'routing_number' => $routingNumber,
            'account_number' => substr($accountNumber, -2),
            'last4digit' => substr($cardNumber, -4),
        ])
            ->assertDatabaseCount(ScheduleTransaction::class, $installmentCount + 1)
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
                'active_negotiation' => true,
                'no_of_installments' => $installmentCount = 8,
                'monthly_amount' => $monthlyAmount = (int) ($discountAmount / $installmentCount),
                'last_month_amount' => $discountAmount - ($monthlyAmount * $installmentCount),
                'negotiate_amount' => $discountAmount,
                'first_pay_date' => today()->toDateString(),
            ]);

        $this->partialMock(USAEpayPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createPaymentProfile')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturnNull();
        });

        $this->assertDatabaseCount(ScheduleTransaction::class, 0);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = 12345)
            ->set('form.method', $this->merchant->merchant_type->value)
            ->set('form.card_number', $cardNumber = '1234567890123456')
            ->set('form.card_holder_name', fake()->realTextBetween(200, 250))
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

        $this->assertDatabaseMissing(PaymentProfile::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'expirity' => $expiry,
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'profile_id' => null,
            'payment_profile_id' => null,
            'shipping_profile_id' => null,
            'routing_number' => Str::substr($routingNumber, -2),
            'account_number' => Str::substr($accountNumber, -2),
            'last4digit' => Str::substr($cardNumber, -4),
        ])
            ->assertDatabaseCount(ScheduleTransaction::class, 0);
    }
}
