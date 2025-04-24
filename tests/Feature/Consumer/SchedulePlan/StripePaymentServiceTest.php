<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\SchedulePlan;

use AllowDynamicProperties;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\ConsumerStatus;
use App\Enums\MerchantName;
use App\Enums\MerchantType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Livewire\Consumer\SchedulePlan;
use App\Mail\AutomatedTemplateMail;
use App\Models\CommunicationStatus;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ConsumerProfile;
use App\Models\Membership;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\StripePaymentDetail;
use App\Models\Subclient;
use App\Models\Transaction;
use App\Services\Consumer\StripePaymentService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Stripe\PaymentIntent;
use Tests\TestCase;

#[AllowDynamicProperties]
class StripePaymentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();

        $this->consumer = Consumer::factory()
            ->for(Subclient::factory()->state(['has_merchant' => true]))
            ->for(ConsumerProfile::factory()->state(['email_permission' => true]))
            ->for($company)
            ->has(ConsumerNegotiation::factory())
            ->has(PaymentProfile::factory()->state(['method' => MerchantType::CC, 'merchant_id' => null]))
            ->create([
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'offer_accepted' => true,
            ]);

        $merchant = Merchant::factory()
            ->for($this->consumer->subclient)
            ->for($company)
            ->create([
                'merchant_name' => MerchantName::STRIPE->value,
                'merchant_type' => MerchantType::CC,
                'stripe_secret_key' => fake()->uuid(),
            ]);

        $this->consumer->paymentProfile->update(['merchant_id' => $merchant->id]);

        CompanyMembership::factory()
            ->for(Membership::factory()->create(['fee' => $this->fee = fake()->numberBetween(0, 50)]))
            ->for($this->consumer->company)
            ->create();

        $this->communicationStatus = CommunicationStatus::factory()->create([
            'code' => CommunicationCode::BALANCE_PAID->value,
            'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
        ]);

        $this->stripePaymentDetail = StripePaymentDetail::factory()->create();
    }

    #[Test]
    public function it_can_successfully_pay_the_remaining_amount_with_a_successful_response(): void
    {
        Mail::fake();

        $this->totalAmount = 0;

        collect(range(1, 10))->each(function () {
            ScheduleTransaction::factory()
                ->create([
                    'consumer_id' => $this->consumer->id,
                    'company_id' => $this->consumer->company_id,
                    'subclient_id' => $this->consumer->subclient_id,
                    'payment_profile_id' => $this->consumer->paymentProfile->id,
                    'schedule_date' => $this->scheduleDate = fake()->date(),
                    'amount' => $this->scheduleAmount = $amount = fake()->numberBetween(1, 100),
                    'status' => TransactionStatus::SCHEDULED->value,
                    'stripe_payment_detail_id' => $this->stripePaymentDetail->id,
                ]);
            $this->totalAmount += $amount;
        });

        $this->partialMock(StripePaymentService::class)
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new StripeSuccessPaymentIntent);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payRemainingAmount')
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $transaction = $this->consumer->transactions->first();

        $this->assertDatabaseMissing(ScheduleTransaction::class, [
            'status' => TransactionStatus::SCHEDULED,
            'transaction_id' => null,
        ]);

        $this->assertDatabaseHas(ScheduleTransaction::class, [
            'transaction_id' => $transaction->id,
        ]);

        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => (new StripeSuccessPaymentIntent)->id,
            'status' => TransactionStatus::SUCCESSFUL,
            'transaction_type' => TransactionType::PARTIAL_PIF,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'payment_profile_id' => $this->consumer->paymentProfile->id,
            'amount' => $this->totalAmount,
            'rnn_share' => $ynShare = number_format($this->totalAmount * $this->fee / 100, 2, thousands_separator: ''),
            'company_share' => number_format((float) ($this->totalAmount - $ynShare), 2, thousands_separator: ''),
            'processing_charges' => null,
            'gateway_response->status' => PaymentIntent::STATUS_SUCCEEDED,
            'status_code' => 'A',
        ]);

        Mail::assertQueued(
            AutomatedTemplateMail::class,
            fn (AutomatedTemplateMail $mail) => $this->consumer->is((fn () => $this->{'consumer'})->call($mail))
        );
    }

    #[Test]
    public function it_can_pay_the_remaining_amount_and_return_a_failed_response(): void
    {
        $this->totalAmount = 0;

        collect(range(1, 10))->each(function () {
            ScheduleTransaction::factory()
                ->create([
                    'consumer_id' => $this->consumer->id,
                    'company_id' => $this->consumer->company_id,
                    'subclient_id' => $this->consumer->subclient_id,
                    'payment_profile_id' => $this->consumer->paymentProfile->id,
                    'schedule_date' => $this->scheduleDate = fake()->date(),
                    'amount' => $this->scheduleAmount = $amount = fake()->numberBetween(1, 100),
                    'status' => TransactionStatus::SCHEDULED->value,
                    'stripe_payment_detail_id' => $this->stripePaymentDetail->id,
                ]);
            $this->totalAmount += $amount;
        });

        $this->partialMock(StripePaymentService::class)
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new StripeCancelPaymentIntent);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payRemainingAmount')
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertDatabaseMissing(ScheduleTransaction::class, ['status' => TransactionStatus::SUCCESSFUL]);

        $this->assertNotEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);

        $this->assertDatabaseHas(Transaction::class, [
            'transaction_id' => (new StripeCancelPaymentIntent)->id,
            'status' => TransactionStatus::FAILED,
            'transaction_type' => TransactionType::PARTIAL_PIF,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'payment_profile_id' => $this->consumer->paymentProfile->id,
            'amount' => $this->totalAmount,
            'rnn_share' => null,
            'company_share' => null,
            'processing_charges' => null,
            'gateway_response->status' => PaymentIntent::STATUS_CANCELED,
            'status_code' => null,
        ]);
    }

    #[Test]
    public function it_can_successfully_pay_the_installment_amount_with_a_successful_response(): void
    {
        Mail::fake();

        $this->scheduleTransaction = ScheduleTransaction::factory()
            ->create([
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'subclient_id' => $this->consumer->subclient_id,
                'payment_profile_id' => $this->consumer->paymentProfile->id,
                'schedule_date' => $this->scheduleDate = fake()->date(),
                'amount' => $this->scheduleAmount = $this->amount = fake()->numberBetween(1, 100),
                'status' => TransactionStatus::SCHEDULED->value,
                'stripe_payment_detail_id' => $this->stripePaymentDetail->id,
            ]);

        $this->partialMock(StripePaymentService::class)
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new StripeSuccessPaymentIntent);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payInstallmentAmount', $this->scheduleTransaction)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $transaction = $this->consumer->transactions->first();

        $this->assertEquals(TransactionStatus::SUCCESSFUL, $this->scheduleTransaction->status);
        $this->assertEquals($transaction->id, $this->scheduleTransaction->transaction_id);

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => (new StripeSuccessPaymentIntent)->id,
            'status' => TransactionStatus::SUCCESSFUL,
            'transaction_type' => TransactionType::INSTALLMENT,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'payment_profile_id' => $this->consumer->paymentProfile->id,
            'amount' => $this->amount,
            'rnn_share' => $ynShare = number_format($this->amount * $this->fee / 100, 2, thousands_separator: ''),
            'company_share' => number_format((float) ($this->amount - $ynShare), 2, thousands_separator: ''),
            'processing_charges' => null,
            'gateway_response->status' => PaymentIntent::STATUS_SUCCEEDED,
            'status_code' => 'A',
        ]);

        Mail::assertQueued(
            AutomatedTemplateMail::class,
            fn (AutomatedTemplateMail $mail) => $this->consumer->is((fn () => $this->{'consumer'})->call($mail))
        );
    }

    #[Test]
    public function it_can_pay_the_installment_amount_and_return_a_failed_response(): void
    {
        $this->scheduleTransaction = ScheduleTransaction::factory()
            ->create([
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'subclient_id' => $this->consumer->subclient_id,
                'payment_profile_id' => $this->consumer->paymentProfile->id,
                'schedule_date' => $this->scheduleDate = fake()->date(),
                'amount' => $this->scheduleAmount = $this->amount = fake()->numberBetween(1, 100),
                'status' => TransactionStatus::SCHEDULED->value,
                'stripe_payment_detail_id' => $this->stripePaymentDetail->id,
            ]);

        $this->partialMock(StripePaymentService::class)
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new StripeCancelPaymentIntent);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payInstallmentAmount', $this->scheduleTransaction)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertEquals(TransactionStatus::SCHEDULED, $this->scheduleTransaction->refresh()->status);

        $this->assertNotEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);

        $this->assertDatabaseHas(Transaction::class, [
            'transaction_id' => (new StripeCancelPaymentIntent)->id,
            'status' => TransactionStatus::FAILED,
            'transaction_type' => TransactionType::INSTALLMENT,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'payment_profile_id' => $this->consumer->paymentProfile->id,
            'amount' => $this->amount,
            'rnn_share' => null,
            'company_share' => null,
            'processing_charges' => null,
            'gateway_response->status' => PaymentIntent::STATUS_CANCELED,
            'status_code' => null,
        ]);
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
