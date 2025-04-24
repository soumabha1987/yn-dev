<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\Payment;

use AllowDynamicProperties;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\ConsumerStatus;
use App\Enums\CustomContentType;
use App\Enums\MembershipTransactionStatus;
use App\Enums\MerchantName;
use App\Enums\MerchantType;
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
use App\Models\CustomContent;
use App\Models\Membership;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use App\Models\YnTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class TilledPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state(['email_permission' => true]))
            ->create([
                'status' => ConsumerStatus::JOINED,
                'subclient_id' => null,
                'current_balance' => $this->amount = 100,
                'pif_discount_percent' => null,
                'custom_offer' => false,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');

        $this->merchant = Merchant::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'merchant_name' => MerchantName::YOU_NEGOTIATE,
                'merchant_type' => MerchantType::CC,
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

        CustomContent::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
            ]);

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
    public function it_can_renders_the_livewire_component(): void
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
                'counter_one_time_amount' => null,
                'account_number' => $this->consumer->account_number,
                'first_pay_date' => today()->toDateString(),
            ]);

        $this->get(route('consumer.payment', $this->consumer->id))
            ->assertSeeLivewire(Payment::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_renders_the_view_file(): void
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
                'counter_one_time_amount' => null,
                'account_number' => $this->consumer->account_number,
                'first_pay_date' => today()->toDateString(),
            ]);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.consumer.payment')
            ->assertDontSeeHtml('<form method="POST" wire:submit="makePayment">')
            ->assertOk();
    }

    #[Test]
    public function it_can_make_success_payment_when_pif_negotiation_plan(): void
    {
        Mail::fake();

        $transactionId = fake()->uuid();
        $status = fake()->randomElement(['processing', 'succeeded']);

        Http::fake(fn () => Http::response([
            'id' => $transactionId,
            'status' => $status,
        ]));

        $this->consumer->company->update([
            'pif_balance_discount_percent' => $pif = fake()->numberBetween(1, 50),
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
                'counter_one_time_amount' => null,
                'account_number' => $this->consumer->account_number,
                'first_pay_date' => today()->toDateString(),
            ]);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->address())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = '12345')
            ->set('form.method', MerchantType::CC->value)
            ->set('form.card_number', '4111111111111111')
            ->set('form.card_holder_name', fake()->name())
            ->set('form.cvv', 124)
            ->set('form.expiry', $expiry = today()->addYear()->format('m/Y'))
            ->set('form.is_terms_accepted', true)
            ->set('form.payment_method_id', $paymentMethodId = fake()->uuid())
            ->set('form.tilled_response', [
                'id' => $paymentMethodId,
                'card' => [
                    'last4' => '1111',
                    'exp_month' => today()->format('m'),
                    'exp_year' => today()->addYear()->format('Y'),
                ],
            ])
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSessionHas('complete-payment')
            ->assertSessionMissing('complete-payment-setup')
            ->assertRedirect(route('consumer.complete_payment', ['consumer' => $this->consumer->id]));

        $this->assertSoftDeleted($this->paymentProfile);
        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);
        $this->assertFalse($this->consumer->has_failed_payment);
        $this->assertTrue($this->consumer->offer_accepted);
        $this->assertTrue($this->consumer->consumerNegotiation->offer_accepted);
        $this->assertEquals('Auto', $this->consumer->consumerNegotiation->approved_by);

        $transaction = $this->consumer->transactions->first();

        $this->assertDatabaseHas(PaymentProfile::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'merchant_id' => $this->merchant->id,
            'method' => $this->merchant->merchant_type,
            'expirity' => $expiry,
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'last4digit' => '1111',
            'account_number' => null,
            'routing_number' => null,
            'profile_id' => $paymentMethodId,
            'payment_profile_id' => $transactionId,
            'deleted_at' => null,
        ])->assertDatabaseHas(ScheduleTransaction::class, [
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'transaction_id' => $transaction->id,
            'transaction_type' => TransactionType::PIF->value,
            'status' => TransactionStatus::SUCCESSFUL->value,
            'attempt_count' => 1,
        ])->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_type' => TransactionType::PIF,
            'transaction_id' => $transactionId,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'status' => TransactionStatus::SUCCESSFUL,
            'amount' => number_format($transactionAmount = ($this->amount - ($this->amount * $pif / 100)), 2, thousands_separator: ''),
            'rnn_share' => number_format($ynShare = $transactionAmount * $this->fee / 100, 2, thousands_separator: ''),
            'company_share' => number_format($transactionAmount - $ynShare, 2, thousands_separator: ''),
            'payment_mode' => MerchantType::CC,
            'gateway_response->id' => $transactionId,
            'gateway_response->status' => $status,
            'superadmin_process' => 0,
            'rnn_invoice_id' => 9001,
        ]);

        $this->assertNotNull(Transaction::query()->where('transaction_id', $transactionId)->first()->rnn_share_pass);

        $this->assertTrue(
            YnTransaction::query()
                ->where([
                    'company_id' => $this->consumer->company_id,
                    'amount' => number_format($transactionAmount * $this->fee / 100, 2, thousands_separator: ''),
                    'email_count' => 0,
                    'sms_count' => 0,
                    'phone_no_count' => 0,
                    'email_cost' => 0,
                    'sms_cost' => 0,
                    'rnn_invoice_id' => 5000,
                    'status' => MembershipTransactionStatus::SUCCESS->value,
                    'response->id' => $transactionId,
                    'response->status' => $status,
                ])
                ->whereNotNull('billing_cycle_start')
                ->whereNotNull('billing_cycle_end')
                ->exists()
        );

        Mail::assertQueued(
            AutomatedTemplateMail::class,
            fn (AutomatedTemplateMail $mail) => $this->consumer->is((fn () => $this->{'consumer'})->call($mail))
        );
    }

    #[Test]
    public function make_failed_payment_profile_when_pif_negotiation_plan(): void
    {
        $this->merchant->update(['merchant_type' => MerchantType::ACH]);

        Http::fake(fn () => Http::response([
            'id' => fake()->uuid(),
            'status' => 'failed',
        ]));

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
                'counter_one_time_amount' => null,
                'account_number' => $this->consumer->account_number,
                'first_pay_date' => today()->toDateString(),
            ]);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->set('form.address', fake()->address())
            ->set('form.city', fake()->city())
            ->set('form.state', fake()->randomElement(State::values()))
            ->set('form.zip', '12345')
            ->set('form.method', MerchantType::ACH->value)
            ->set('form.account_number', '2345673')
            ->set('form.routing_number', '021000021')
            ->set('form.is_terms_accepted', true)
            ->set('form.payment_method_id', $paymentMethodId = fake()->uuid())
            ->set('form.tilled_response', [
                'id' => $paymentMethodId,
                'ach_debit' => [
                    'last2' => '73',
                    'routing_number' => '021000021',
                ],
            ])
            ->call('makePayment')
            ->assertHasNoErrors()
            ->assertSessionMissing('complete-payment')
            ->assertSessionMissing('complete-payment-setup')
            ->assertOk();

        $this->assertNotSoftDeleted($this->paymentProfile);

        $this->assertNotEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);

        $this->assertDatabaseCount(ScheduleTransaction::class, 0)
            ->assertDatabaseCount(Transaction::class, 0)
            ->assertDatabaseCount(AutomatedCommunicationHistory::class, 0);
    }

    #[Test]
    public function it_can_tilled_installment_success_payment(): void
    {
        $this->consumer->company()->update([
            'ppa_balance_discount_percent' => $ppaPercentage = 10,
        ]);

        config(['services.tilled.publishable_key' => fake()->uuid()]);

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

        Http::fake(fn () => Http::response([
            'id' => $this->transactionId = fake()->uuid(),
        ]));

        $this->assertDatabaseCount(ScheduleTransaction::class, 0);

        $paymentMethodId = fake()->uuid();
        $tilledResponse = [
            'id' => $paymentMethodId,
            'status' => 'success',
            'card' => [
                'last4' => '4242',
                'exp_month' => '12',
                'exp_year' => '25',
            ],
        ];

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->address())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = 12345)
            ->set('form.method', $this->merchant->merchant_type->value)
            ->set('form.card_number', $cardNumber = '4242424242424242')
            ->set('form.card_holder_name', fake()->name())
            ->set('form.cvv', 123)
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', now()->addYear()->format('m/Y'))
            ->set('form.tilled_response', $tilledResponse)
            ->set('form.payment_method_id', $paymentMethodId)
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
            'expirity' => '12/25',
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'deleted_at' => null,
            'profile_id' => $paymentMethodId,
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
    public function renders_make_failed_payment_profile_when_installment_negotiation_plan(): void
    {
        $this->consumer->company()->update([
            'ppa_balance_discount_percent' => $ppaPercentage = 10,
        ]);

        config(['services.tilled.publishable_key' => fake()->uuid()]);

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

        $paymentMethodId = fake()->uuid();
        $tilledResponse = [
            'id' => $paymentMethodId,
            'status' => 'failed',
            'card' => [
                'last4' => '4242',
                'exp_month' => '12',
                'exp_year' => '25',
            ],
        ];

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', $address = fake()->address())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = 12345)
            ->set('form.method', $this->merchant->merchant_type->value)
            ->set('form.card_number', $cardNumber = '4242424242424242')
            ->set('form.card_holder_name', fake()->name())
            ->set('form.cvv', 123)
            ->set('form.is_terms_accepted', true)
            ->set('form.expiry', $expiry = now()->addYear()->format('m/Y'))
            ->set('form.tilled_response', $tilledResponse)
            ->set('form.payment_method_id', $paymentMethodId)
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
            'expirity' => '12/25',
            'fname' => $this->consumer->first_name,
            'lname' => $this->consumer->last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'deleted_at' => null,
            'profile_id' => $paymentMethodId,
            'last4digit' => substr($cardNumber, -4),
        ])
            ->assertDatabaseCount(ScheduleTransaction::class, 0)
            ->assertDatabaseMissing(ScheduleTransaction::class, [
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
}
