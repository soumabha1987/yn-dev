<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\ConsumerStatus;
use App\Enums\MerchantName;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\MerchantPaymentException;
use App\Jobs\StripeSchedulePaymentJob;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\CommunicationStatus;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ConsumerProfile;
use App\Models\ConsumerUnsubscribe;
use App\Models\Membership;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\StripePaymentDetail;
use App\Models\Transaction;
use App\Services\StripePaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Stripe\PaymentIntent;
use Tests\TestCase;

class StripeSchedulePaymentJobTest extends TestCase
{
    public int $fee = 10;

    #[Test]
    public function it_can_throw_exception_for_failed_response_and_also_failed_transaction_entry(): void
    {
        Queue::fake()->except(StripeSchedulePaymentJob::class);

        $scheduleTransaction = $this->prepareMerchant();

        $this->expectException(MerchantPaymentException::class);
        $this->expectExceptionCode(0);

        StripeSchedulePaymentJob::dispatch($scheduleTransaction);

        $communicationCode = match ($scheduleTransaction->transaction_type) {
            TransactionType::PIF => CommunicationCode::PAYMENT_FAILED_WHEN_PIF,
            TransactionType::INSTALLMENT => CommunicationCode::PAYMENT_FAILED_WHEN_INSTALLMENT,
            default => null,
        };

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $scheduleTransaction->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === $communicationCode
        );
    }

    #[Test]
    public function it_can_create_success_transaction_for_stripe_merchant(): void
    {
        $this->travelTo(now()->addDay());

        $scheduleTransaction = $this->prepareMerchant();

        CommunicationStatus::factory()->create([
            'code' => CommunicationCode::BALANCE_PAID,
            'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
        ]);

        $this->mock(StripePaymentService::class)
            ->shouldReceive('proceedPayment')
            ->withAnyArgs()
            ->andReturn(new StripeResponse);

        StripeSchedulePaymentJob::dispatch($scheduleTransaction);

        $transaction = $scheduleTransaction->consumer->transactions()->first();

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => 'test-transaction-id',
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'gateway_response->last2' => Cache::get('stripe_response')['last2'],
            'gateway_response->ach_debit->name' => Cache::get('stripe_response')['ach_debit']['name'],
            'gateway_response->ach_debit->address' => Cache::get('stripe_response')['ach_debit']['address'],
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => $amount = number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'rnn_share' => $ynShare = number_format((float) ($amount * $this->fee / 100), 2, thousands_separator: ''),
            'company_share' => number_format((float) ($amount - $ynShare), 2, thousands_separator: ''),
            'status' => TransactionStatus::SUCCESSFUL->value,
            'status_code' => 'A',
        ]);

        // Because we are updating in command!
        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertNotNull($scheduleTransaction->last_attempted_at);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $scheduleTransaction->status);
        $this->assertEquals($transaction->id, $scheduleTransaction->transaction_id);

        $this->assertEquals(ConsumerStatus::SETTLED, $scheduleTransaction->consumer->refresh()->status);
        $this->assertEquals(false, $scheduleTransaction->consumer->has_failed_payment);
        $this->assertEquals(0, $scheduleTransaction->consumer->current_balance);

        $paymentPlanCurrentBalance = number_format(max(0, (float) $scheduleTransaction->consumer->consumerNegotiation->one_time_settlement - (float) $scheduleTransaction->amount), 2, thousands_separator: '');

        $this->assertEquals($paymentPlanCurrentBalance, number_format((float) $scheduleTransaction->consumer->consumerNegotiation->refresh()->payment_plan_current_balance, 2, thousands_separator: ''));
    }

    #[Test]
    public function it_can_failed_transaction_for_stripe_merchant(): void
    {
        Queue::fake()->except(StripeSchedulePaymentJob::class);

        $this->travelTo(now()->addDay());

        $scheduleTransaction = $this->prepareMerchant();

        $this->mock(StripePaymentService::class)
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new StripeFailedResponse);

        $this->assertThrows(
            fn () => StripeSchedulePaymentJob::dispatch($scheduleTransaction),
            MerchantPaymentException::class,
            'Oops! Stripe payment for installment is not working'
        );

        $communicationCode = match ($scheduleTransaction->transaction_type) {
            TransactionType::PIF => CommunicationCode::PAYMENT_FAILED_WHEN_PIF,
            TransactionType::INSTALLMENT => CommunicationCode::PAYMENT_FAILED_WHEN_INSTALLMENT,
            default => null,
        };

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $scheduleTransaction->consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === $communicationCode
        );

        $this->assertDatabaseHas(Transaction::class, [
            'transaction_id' => 'test-transaction-id',
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'gateway_response->last2' => Cache::get('stripe_response')['last2'],
            'gateway_response->ach_debit->name' => Cache::get('stripe_response')['ach_debit']['name'],
            'gateway_response->ach_debit->address' => Cache::get('stripe_response')['ach_debit']['address'],
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'rnn_share' => null,
            'company_share' => null,
            'status' => TransactionStatus::FAILED->value,
            'status_code' => null,
        ]);
    }

    #[Test]
    public function it_can_create_unsubscribe_consumer_status_settled_for_stripe_merchant(): void
    {
        Mail::fake();

        $this->travelTo(now()->addDay());

        $scheduleTransaction = $this->prepareMerchant();

        ConsumerUnsubscribe::factory()
            ->for($scheduleTransaction->consumer)
            ->create([
                'company_id' => $scheduleTransaction->consumer->company_id,
                'email' => $scheduleTransaction->consumer->email1,
            ]);

        Log::shouldReceive('channel')->once()->with('daily')->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('When sending manual an email at that time consumer is not subscribe for that', [
                'consumer_id' => $scheduleTransaction->consumer->id,
                'communication_code' => CommunicationCode::BALANCE_PAID,
            ])
            ->andReturnNull();

        $this->mock(StripePaymentService::class)
            ->shouldReceive('proceedPayment')
            ->withAnyArgs()
            ->andReturn(new StripeResponse);

        StripeSchedulePaymentJob::dispatch($scheduleTransaction);

        $transaction = $scheduleTransaction->consumer->transactions()->first();

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => 'test-transaction-id',
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'gateway_response->last2' => Cache::get('stripe_response')['last2'],
            'gateway_response->ach_debit->name' => Cache::get('stripe_response')['ach_debit']['name'],
            'gateway_response->ach_debit->address' => Cache::get('stripe_response')['ach_debit']['address'],
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => $amount = number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'rnn_share' => $ynShare = number_format((float) ($amount * $this->fee / 100), 2, thousands_separator: ''),
            'company_share' => number_format((float) ($amount - $ynShare), 2, thousands_separator: ''),
            'status' => TransactionStatus::SUCCESSFUL->value,
            'status_code' => 'A',
        ]);

        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertNotNull($scheduleTransaction->last_attempted_at);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $scheduleTransaction->status);
        $this->assertEquals($transaction->id, $scheduleTransaction->transaction_id);

        $this->assertEquals(ConsumerStatus::SETTLED, $scheduleTransaction->consumer->refresh()->status);
        $this->assertEquals(false, $scheduleTransaction->consumer->has_failed_payment);
        $this->assertEquals(0, $scheduleTransaction->consumer->current_balance);

        $paymentPlanCurrentBalance = number_format(max(0, (float) $scheduleTransaction->consumer->consumerNegotiation->one_time_settlement - (float) $scheduleTransaction->amount), 2, thousands_separator: '');

        $this->assertEquals($paymentPlanCurrentBalance, number_format((float) $scheduleTransaction->consumer->consumerNegotiation->refresh()->payment_plan_current_balance, 2, thousands_separator: ''));

        Mail::assertNothingQueued();
    }

    private function prepareMerchant(): ScheduleTransaction
    {
        $merchant = Merchant::factory()->create(['merchant_name' => MerchantName::STRIPE]);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state(['text_permission' => true, 'email_permission' => true]))
            ->has(PaymentProfile::factory()->for($merchant))
            ->create([
                'status' => ConsumerStatus::PAYMENT_ACCEPTED->value,
                'offer_accepted' => true,
            ]);

        CompanyMembership::factory()
            ->for($consumer->company)
            ->for(Membership::factory()->create(['fee' => $this->fee]))
            ->create();

        ConsumerNegotiation::factory()->create([
            'consumer_id' => $consumer->id,
            'payment_plan_current_balance' => null,
            'negotiation_type' => NegotiationType::PIF->value,
            'offer_accepted' => true,
            'one_time_settlement' => fake()->randomFloat(2, 1, 5000),
        ]);

        $stripePaymentDetails = StripePaymentDetail::query()->create();

        return ScheduleTransaction::factory()
            ->create([
                'transaction_type' => fake()->randomElement([TransactionType::INSTALLMENT, TransactionType::PIF]),
                'consumer_id' => $consumer->id,
                'status' => TransactionStatus::SCHEDULED->value,
                'attempt_count' => 1,
                'schedule_date' => now()->subWeek()->toDateString(),
                'amount' => $consumer->current_balance,
                'stripe_payment_detail_id' => $stripePaymentDetails->id,
                'revenue_share_percentage' => $this->fee,
            ]);
    }
}

class StripeResponse
{
    public string $status = PaymentIntent::STATUS_SUCCEEDED;

    public string $id = 'test-transaction-id';

    public function toArray(): array
    {
        Cache::put('stripe_response', $data = [
            'last2' => fake()->randomNumber(2, true),
            'ach_debit' => [
                'name' => fake()->name(),
                'address' => fake()->address(),
            ],
        ]);

        return $data;
    }
}

class StripeFailedResponse extends StripeResponse
{
    public string $status = PaymentIntent::STATUS_CANCELED;
}
