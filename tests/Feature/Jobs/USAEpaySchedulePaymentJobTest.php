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
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Jobs\USAEpaySchedulePaymentJob;
use App\Models\AutomatedCommunicationHistory;
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
use App\Services\UsaepayPaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class USAEpaySchedulePaymentJobTest extends TestCase
{
    public int $fee = 10;

    #[Test]
    public function it_can_throw_exception_for_failed_response_and_also_failed_transaction_entry(): void
    {
        Queue::fake()->except(USAEpaySchedulePaymentJob::class);

        $scheduleTransaction = $this->prepareMerchant();

        $this->expectException(MerchantPaymentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Oops! USAepay payment service has something went wrong');

        USAEpaySchedulePaymentJob::dispatchSync($scheduleTransaction);

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
    public function it_can_create_success_transaction_for_usa_epay_merchant(): void
    {
        $this->travelTo(now()->addDay());

        $scheduleTransaction = $this->prepareMerchant();

        CommunicationStatus::factory()->create([
            'code' => CommunicationCode::BALANCE_PAID,
            'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
        ]);

        $this->mock(UsaepayPaymentService::class)
            ->shouldReceive('proceedPayment')
            ->withAnyArgs()
            ->andReturn(new USAEpayResponse);

        USAEpaySchedulePaymentJob::dispatch($scheduleTransaction);

        $transaction = $scheduleTransaction->consumer->transactions()->first();

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => 'test-transaction-id',
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'gateway_response->RefNum' => 'test-transaction-id',
            'gateway_response->ResultCode' => 'A',
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => $amount = number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'rnn_share' => $yn_Share = number_format((float) ($amount * $this->fee / 100), 2, thousands_separator: ''),
            'company_share' => number_format((float) ($amount - $yn_Share), 2, thousands_separator: ''),
            'status' => TransactionStatus::SUCCESSFUL->value,
            'status_code' => 'A',
            'rnn_share_pass' => null,
        ]);

        // Because we are updating in the command!
        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertNotNull($scheduleTransaction->last_attempted_at);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $scheduleTransaction->status);
        $this->assertEquals($transaction->id, $scheduleTransaction->transaction_id);

        $this->assertEquals(ConsumerStatus::SETTLED, $scheduleTransaction->consumer->refresh()->status);
        $this->assertEquals(false, $scheduleTransaction->consumer->has_failed_payment);
        $this->assertEquals(0, $scheduleTransaction->consumer->current_balance);

        $paymentPlanCurrentBalance = number_format(max(0, (float) $scheduleTransaction->consumer->consumerNegotiation->negotiate_amount - (float) $scheduleTransaction->amount), 2, thousands_separator: '');

        $this->assertEquals($paymentPlanCurrentBalance, number_format((float) $scheduleTransaction->consumer->consumerNegotiation->refresh()->payment_plan_current_balance, 2, thousands_separator: ''));
    }

    #[Test]
    public function it_can_failed_transaction_for_usa_epay_merchant(): void
    {
        Queue::fake()->except(USAEpaySchedulePaymentJob::class);

        $this->travelTo(now()->addDay());

        $scheduleTransaction = $this->prepareMerchant();

        $this->mock(UsaepayPaymentService::class)
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new USAEpayFailedResponse);

        $this->assertThrows(
            fn () => USAEpaySchedulePaymentJob::dispatch($scheduleTransaction),
            MerchantPaymentException::class,
            'Oops! Something went wrong in usaepay installment payment'
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
            'gateway_response->RefNum' => 'test-transaction-id',
            'gateway_response->ResultCode' => 'B',
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'company_share' => null,
            'rnn_share' => null,
            'status' => TransactionStatus::FAILED->value,
            'status_code' => 'B',
        ]);
    }

    #[Test]
    public function it_can_create_success_transaction_of_unsubscribe_consumer_status_settled_for_usa_epay_merchant(): void
    {
        Queue::fake()->except(USAEpaySchedulePaymentJob::class);

        $this->travelTo(now()->addDay());

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

        $this->mock(UsaepayPaymentService::class)
            ->shouldReceive('proceedPayment')
            ->withAnyArgs()
            ->andReturn(new USAEpayResponse);

        USAEpaySchedulePaymentJob::dispatchSync($scheduleTransaction);

        $transaction = $scheduleTransaction->consumer->transactions()->first();

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => 'test-transaction-id',
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'gateway_response->RefNum' => 'test-transaction-id',
            'gateway_response->ResultCode' => 'A',
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

        $paymentPlanCurrentBalance = number_format(max(0, (float) $scheduleTransaction->consumer->consumerNegotiation->negotiate_amount - (float) $scheduleTransaction->amount), 2, thousands_separator: '');

        $this->assertEquals($paymentPlanCurrentBalance, number_format((float) $scheduleTransaction->consumer->consumerNegotiation->refresh()->payment_plan_current_balance, 2, thousands_separator: ''));

        $this->assertDatabaseCount(AutomatedCommunicationHistory::class, 0);

        Queue::assertNothingPushed();
    }

    private function prepareMerchant(): ScheduleTransaction
    {
        $merchant = Merchant::factory()->create(['merchant_name' => MerchantName::USA_EPAY]);

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
            'negotiation_type' => NegotiationType::INSTALLMENT->value,
            'offer_accepted' => true,
            'negotiate_amount' => fake()->randomFloat(2, 1, 5000),
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

class USAEpayResponse
{
    public string $RefNum = 'test-transaction-id';

    public string $ResultCode = 'A';
}

class USAEpayFailedResponse extends USAEpayResponse
{
    public string $ResultCode = 'B';
}
