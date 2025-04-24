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
use App\Models\StripePaymentDetail;
use App\Models\Transaction;
use App\Services\Consumer\StripePaymentService;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Stripe\PaymentIntent;
use Tests\TestCase;

#[AllowDynamicProperties]
class StripePaymentTest extends TestCase
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
                'current_balance' => $this->amount = 1000,
                'pif_discount_percent' => null,
                'subclient_id' => null,
                'custom_offer' => false,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');

        $this->merchant = Merchant::factory()
            ->for($this->consumer->company)
            ->create([
                'merchant_name' => MerchantName::STRIPE,
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

        $automatedTemplate = AutomatedTemplate::factory()->email()->create();

        CompanyMembership::factory()
            ->for(Membership::factory()->create(['fee' => $this->fee = fake()->numberBetween(0, 50)]))
            ->for($this->consumer->company)
            ->create();

        $this->communicationStatus = CommunicationStatus::factory()
            ->create([
                'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
                'automated_email_template_id' => $automatedTemplate->id,
                'automated_sms_template_id' => $automatedTemplate->id,
                'code' => CommunicationCode::BALANCE_PAID,
            ]);
    }

    #[Test]
    public function it_can_make_success_payment_profile_when_installment_negotiation_plan(): void
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
                'payment_plan_current_balance' => 0,
            ]);

        $this->partialMock(StripePaymentService::class)
            ->shouldReceive('createOrUpdateCustomerProfile')
            ->atLeast()
            ->once()
            ->andReturn(true);

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
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', $expiry = now()->addYear()->format('m/Y'))
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSessionMissing('complete-payment')
            ->assertSessionHas('complete-payment-setup')
            ->assertOk();

        $this->assertSoftDeleted($this->paymentProfile);

        $this->assertDatabaseCount(ScheduleTransaction::class, 9)
            ->assertDatabaseHas(PaymentProfile::class, [
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'method' => $paymentMethod,
                'last4digit' => Str::substr($cardNumber, -4),
                'expirity' => $expiry,
                'fname' => $this->consumer->first_name,
                'lname' => $this->consumer->last_name,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip' => $zip,
                'profile_id' => null,
            ])
            ->assertDatabaseHas(ScheduleTransaction::class, [
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company->id,
                'status' => TransactionStatus::SCHEDULED,
                'status_code' => 111,
                'amount' => $monthlyAmount,
                'schedule_date' => today()->toDateString(),
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

        $this->merchant->update(['stripe_secret_key' => null]);

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
                'payment_plan_current_balance' => 0,
            ]);

        $this->assertDatabaseCount(ScheduleTransaction::class, 0);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', fake()->address())
            ->set('form.city', fake()->city())
            ->set('form.state', fake()->randomElement(State::values()))
            ->set('form.zip', 12345)
            ->set('form.method', $this->merchant->merchant_type->value)
            ->set('form.card_number', '1234567890123456')
            ->set('form.card_holder_name', fake()->name())
            ->set('form.cvv', 123)
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', now()->addYear()->format('m/Y'))
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSessionMissing('complete-payment')
            ->assertSessionMissing('complete-payment-setup')
            ->assertOk();

        $this->assertNotSoftDeleted($this->paymentProfile);
        $this->assertEquals(ConsumerStatus::JOINED, $this->consumer->refresh()->status);
        $this->assertDatabaseCount(ScheduleTransaction::class, 0);
    }

    #[Test]
    public function stripe_immediate_pif_payment_with_success_response(): void
    {
        StripePaymentDetail::factory()->create(['consumer_id' => $this->consumer->id]);

        $this->partialMock(StripePaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createOrUpdateCustomerProfile')
                ->atLeast()
                ->once()
                ->andReturn();

            $mock->shouldReceive('proceedPayment')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn(new StripeSuccessPaymentIntent);
        });

        $this->consumer->company->update([
            'pif_balance_discount_percent' => $pif = fake()->numberBetween(1, 40),
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
            'deleted_at' => null,
        ])->assertDatabaseHas(Transaction::class, [
            'transaction_id' => (new StripeSuccessPaymentIntent)->id,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'amount' => number_format($transactionAmount = ($this->amount - ($this->amount * $pif / 100)), 2, thousands_separator: ''),
            'rnn_share' => number_format($ynShare = $transactionAmount * $this->fee / 100, 2, thousands_separator: ''),
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
    public function stripe_immediate_pif_payment_with_failed_response(): void
    {
        StripePaymentDetail::factory()->create(['consumer_id' => $this->consumer->id]);

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

        $this->partialMock(StripePaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createOrUpdateCustomerProfile')
                ->atLeast()
                ->once()
                ->andReturn();

            $mock->shouldReceive('proceedPayment')
                ->atLeast()
                ->once()
                ->withAnyArgs()
                ->andReturn(new StripeCancelPaymentIntent);
        });

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
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', $expiry = now()->addYear()->format('m/Y'))
            ->call('makePayment')
            ->assertSessionMissing('complete-payment')
            ->assertSessionMissing('complete-payment-setup')
            ->assertOk();

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
            'profile_id' => null,
            'payment_profile_id' => null,
            'shipping_profile_id' => null,
            'last4digit' => Str::substr($cardNumber, -4),
            'deleted_at' => null,
        ])
            ->assertDatabaseCount(ScheduleTransaction::class, 0)
            ->assertDatabaseCount(Transaction::class, 0)
            ->assertDatabaseCount(AutomatedCommunicationHistory::class, 0);

    }
}

class StripeSuccessPaymentIntent
{
    public string $id = 'Test Transaction Id';

    public string $status = PaymentIntent::STATUS_SUCCEEDED;

    public function toArray(): array
    {
        return ['id' => $this->id, 'status' => $this->status];
    }
}

class StripeCancelPaymentIntent
{
    public string $id = 'Test Transaction Id';

    public string $status = PaymentIntent::STATUS_CANCELED;

    public function toArray(): array
    {
        return ['id' => $this->id, 'status' => $this->status];
    }
}
