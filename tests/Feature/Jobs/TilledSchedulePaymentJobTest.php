<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\ConsumerStatus;
use App\Enums\MembershipTransactionStatus;
use App\Enums\MerchantName;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\MerchantPaymentException;
use App\Jobs\TilledSchedulePaymentJob;
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
use App\Models\Transaction;
use App\Models\YnTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TilledSchedulePaymentJobTest extends TestCase
{
    public int $fee = 10;

    #[Test]
    public function it_can_throw_exception_for_failed_response_and_also_failed_transaction_entry(): void
    {
        Queue::fake()->except(TilledSchedulePaymentJob::class);

        $scheduleTransaction = $this->prepareMerchant();

        $this->expectException(MerchantPaymentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Oops! Something went wrong when installment payment of the younegotiate merchant');

        TilledSchedulePaymentJob::dispatchSync($scheduleTransaction);

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
    public function it_can_create_success_transaction_for_tilled_merchant(): void
    {
        $this->travelTo(now()->addDay());

        $transactionId = fake()->uuid();
        $status = fake()->randomElement(['processing', 'succeeded']);

        Http::fake(fn () => Http::response(['id' => $transactionId, 'status' => $status]));

        $scheduleTransaction = $this->prepareMerchant();

        CommunicationStatus::factory()->create([
            'code' => CommunicationCode::BALANCE_PAID,
            'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
        ]);

        TilledSchedulePaymentJob::dispatchSync($scheduleTransaction);

        $transaction = $scheduleTransaction->consumer->transactions()->first();

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => $transactionId,
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'gateway_response->status' => $status,
            'gateway_response->id' => $transactionId,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => $amount = number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'processing_charges' => null,
            'rnn_share' => $ynShare = number_format((float) ($amount * $this->fee / 100), 2, thousands_separator: ''),
            'company_share' => number_format((float) ($amount - $ynShare), 2, thousands_separator: ''),
            'status' => TransactionStatus::SUCCESSFUL->value,
            'status_code' => 200,
            'rnn_share_pass' => now(),
        ]);

        $this->assertDatabaseHas(YnTransaction::class, [
            'company_id' => $scheduleTransaction->company_id,
            'amount' => number_format((float) ($amount * $this->fee / 100), 2, thousands_separator: ''),
            'billing_cycle_start' => now()->toDateTimeString(),
            'billing_cycle_end' => now()->toDateTimeString(),
            'email_count' => 0,
            'sms_count' => 0,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
            'rnn_invoice_id' => 5000,
            'status' => MembershipTransactionStatus::SUCCESS->value,
            'response->id' => $transactionId,
            'response->status' => $status,
        ]);

        // Because we are updating in command!
        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertNotNull($scheduleTransaction->last_attempted_at);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $scheduleTransaction->status);
        $this->assertEquals($transaction->id, $scheduleTransaction->transaction_id);

        $this->assertEquals(ConsumerStatus::SETTLED, $scheduleTransaction->consumer->refresh()->status);
        $this->assertEquals(false, $scheduleTransaction->consumer->has_failed_payment);
        $this->assertEquals(0, $scheduleTransaction->consumer->current_balance);

        $paymentPlanCurrentBalance = number_format(max(0, (float) $scheduleTransaction->consumer->consumerNegotiation->counter_one_time_amount - (float) $scheduleTransaction->amount), 2, thousands_separator: '');

        $this->assertEquals($paymentPlanCurrentBalance, number_format((float) $scheduleTransaction->consumer->consumerNegotiation->refresh()->payment_plan_current_balance, 2, thousands_separator: ''));
    }

    #[Test]
    public function it_can_failed_transaction_for_usa_tilled_merchant(): void
    {
        Queue::fake()->except(TilledSchedulePaymentJob::class);

        $this->travelTo(now()->addDay());

        $scheduleTransaction = $this->prepareMerchant();

        CommunicationStatus::factory()->create([
            'code' => CommunicationCode::PAYMENT_FAILED_WHEN_INSTALLMENT,
            'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
        ]);

        $message = fake()->sentence();

        Http::fake(fn () => Http::response(['message' => $message], 403));

        $this->assertThrows(
            fn () => TilledSchedulePaymentJob::dispatchSync($scheduleTransaction),
            MerchantPaymentException::class,
            'Oops! Something went wrong when installment payment of the younegotiate merchant'
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
            'transaction_id' => null,
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'gateway_response->message' => $message,
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'processing_charges' => null,
            'company_share' => null,
            'rnn_share' => null,
            'status' => TransactionStatus::FAILED->value,
            'status_code' => 403,
        ]);

        $this->assertDatabaseCount(YnTransaction::class, 0);
    }

    #[Test]
    public function it_can_create_unsubscribe_consumer_status_settled_for_tilled_merchant(): void
    {
        $this->travelTo(now()->addDay());

        $transactionId = fake()->uuid();
        $status = fake()->randomElement(['processing', 'succeeded']);

        Http::fake(fn () => Http::response([
            'id' => $transactionId,
            'status' => $status,
        ]));

        $scheduleTransaction = $this->prepareMerchant();

        ConsumerUnsubscribe::factory()->create([
            'company_id' => $scheduleTransaction->consumer->company_id,
            'consumer_id' => $scheduleTransaction->consumer->id,
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

        TilledSchedulePaymentJob::dispatchSync($scheduleTransaction);

        $transaction = $scheduleTransaction->consumer->transactions()->first();

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => $transactionId,
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'gateway_response->status' => $status,
            'gateway_response->id' => $transactionId,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => $amount = number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'processing_charges' => null,
            'rnn_share' => $ynShare = number_format((float) ($amount * $this->fee / 100), 2, thousands_separator: ''),
            'company_share' => number_format((float) ($amount - $ynShare), 2, thousands_separator: ''),
            'status' => TransactionStatus::SUCCESSFUL->value,
            'status_code' => 200,
        ]);

        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertNotNull($scheduleTransaction->last_attempted_at);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $scheduleTransaction->status);
        $this->assertEquals($transaction->id, $scheduleTransaction->transaction_id);

        $this->assertEquals(ConsumerStatus::SETTLED, $scheduleTransaction->consumer->refresh()->status);
        $this->assertEquals(false, $scheduleTransaction->consumer->has_failed_payment);
        $this->assertEquals(0, $scheduleTransaction->consumer->current_balance);

        $paymentPlanCurrentBalance = number_format(max(0, (float) $scheduleTransaction->consumer->consumerNegotiation->counter_one_time_amount - (float) $scheduleTransaction->amount), 2, thousands_separator: '');

        $this->assertEquals($paymentPlanCurrentBalance, number_format((float) $scheduleTransaction->consumer->consumerNegotiation->refresh()->payment_plan_current_balance, 2, thousands_separator: ''));
    }

    private function prepareMerchant(): ScheduleTransaction
    {
        $merchant = Merchant::factory()->create(['merchant_name' => MerchantName::YOU_NEGOTIATE]);

        $consumerProfile = ConsumerProfile::factory()->create();

        $consumer = Consumer::factory()
            ->has(PaymentProfile::factory()->for($merchant))
            ->create([
                'consumer_profile_id' => $consumerProfile->id,
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
            'counter_offer_accepted' => true,
            'offer_accepted' => false,
            'counter_one_time_amount' => fake()->randomFloat(2, 1, 5000),
        ]);

        return ScheduleTransaction::factory()
            ->create([
                'transaction_type' => fake()->randomElement([TransactionType::INSTALLMENT, TransactionType::PIF]),
                'consumer_id' => $consumer->id,
                'status' => TransactionStatus::SCHEDULED->value,
                'attempt_count' => 1,
                'schedule_date' => now()->subWeek()->toDateString(),
                'amount' => $consumer->current_balance,
                'revenue_share_percentage' => $this->fee,
            ]);
    }
}
