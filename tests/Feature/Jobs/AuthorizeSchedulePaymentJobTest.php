<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\ConsumerStatus;
use App\Enums\MerchantName;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\MerchantPaymentException;
use App\Jobs\AuthorizeSchedulePaymentJob;
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
use App\Services\AuthorizePaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorizeSchedulePaymentJobTest extends TestCase
{
    public int $fee = 10;

    #[Test]
    public function it_can_throw_exception_for_failed_response_and_also_failed_transaction_entry(): void
    {
        Queue::fake()->except(AuthorizeSchedulePaymentJob::class);

        $scheduleTransaction = $this->prepareMerchant();

        $this->expectException(MerchantPaymentException::class);
        $this->expectExceptionMessage('Oops! Something went wrong in authorize for installment payment');
        $this->expectExceptionCode(0);

        // This will call actual api of Authorize and it will return failure because the our transaction key is not matched...
        AuthorizeSchedulePaymentJob::dispatchSync($scheduleTransaction);

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
    public function it_can_create_success_transaction_for_authorize_merchant(): void
    {
        $this->travelTo(now()->addDay());

        $scheduleTransaction = $this->prepareMerchant();

        CommunicationStatus::factory()->create([
            'code' => CommunicationCode::BALANCE_PAID,
            'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
        ]);

        $service = $this->mock(AuthorizePaymentService::class);
        $service->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new AuthorizeTransactionResponse);

        AuthorizeSchedulePaymentJob::dispatchSync($scheduleTransaction);

        $transaction = $scheduleTransaction->consumer->transactions()->first();

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => Cache::get('transaction_id'),
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => $amount = number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'rnn_share' => $ynShare = number_format((float) ($amount * $this->fee / 100), 2, thousands_separator: ''),
            'company_share' => number_format((float) ($amount - $ynShare), 2, thousands_separator: ''),
            'status' => TransactionStatus::SUCCESSFUL->value,
            'status_code' => 201,
            'rnn_share_pass' => null,
        ]);

        // Because we are updating in command.
        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertNotNull($scheduleTransaction->last_attempted_at);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $scheduleTransaction->status);
        $this->assertEquals($transaction->id, $scheduleTransaction->transaction_id);

        $this->assertEquals(ConsumerStatus::SETTLED, $scheduleTransaction->consumer->refresh()->status);
        $this->assertEquals(false, $scheduleTransaction->consumer->has_failed_payment);
        $this->assertEquals(0, $scheduleTransaction->consumer->current_balance);

        $this->assertEquals(0, $scheduleTransaction->consumer->consumerNegotiation->refresh()->payment_plan_current_balance);
    }

    #[Test]
    public function it_can_create_success_transaction_with_twice_membership_for_authorize_merchant(): void
    {
        $this->travelTo(now()->addDay());

        $scheduleTransaction = $this->prepareMerchant();

        CommunicationStatus::factory()->create([
            'code' => CommunicationCode::BALANCE_PAID,
            'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
        ]);

        CompanyMembership::factory()
            ->for($scheduleTransaction->consumer->company)
            ->for(Membership::factory()->create(['fee' => 20]))
            ->create(['current_plan_end' => now()->addMonth()]);

        $service = $this->mock(AuthorizePaymentService::class);
        $service->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new AuthorizeTransactionResponse);

        AuthorizeSchedulePaymentJob::dispatchSync($scheduleTransaction);

        $transaction = $scheduleTransaction->consumer->transactions()->first();

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => Cache::get('transaction_id'),
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => $amount = number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'rnn_share' => $ynShare = number_format((float) ($amount * $this->fee / 100), 2, thousands_separator: ''),
            'company_share' => number_format((float) ($amount - $ynShare), 2, thousands_separator: ''),
            'status' => TransactionStatus::SUCCESSFUL->value,
            'status_code' => 201,
        ]);

        // Because we are updating in command.
        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertNotNull($scheduleTransaction->last_attempted_at);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $scheduleTransaction->status);
        $this->assertEquals($transaction->id, $scheduleTransaction->transaction_id);

        $this->assertEquals(ConsumerStatus::SETTLED, $scheduleTransaction->consumer->refresh()->status);
        $this->assertEquals(false, $scheduleTransaction->consumer->has_failed_payment);
        $this->assertEquals(0, $scheduleTransaction->consumer->current_balance);

        $this->assertEquals(0, $scheduleTransaction->consumer->consumerNegotiation->refresh()->payment_plan_current_balance);
    }

    #[Test]
    public function it_can_failed_transaction_for_authorize_merchant(): void
    {
        Queue::fake()->except(AuthorizeSchedulePaymentJob::class);

        $this->travelTo(now()->addDay());

        $scheduleTransaction = $this->prepareMerchant();

        $service = $this->mock(AuthorizePaymentService::class);
        $service->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new FailedAuthorizeTransactionResponse);

        $this->assertThrows(
            fn () => AuthorizeSchedulePaymentJob::dispatchSync($scheduleTransaction),
            MerchantPaymentException::class,
            'Oops! Something went wrong in authorize for installment payment'
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
            'transaction_id' => Cache::get('transaction_id'),
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'rnn_share' => null,
            'company_share' => null,
            'status' => TransactionStatus::FAILED->value,
            'status_code' => 502,
        ]);
    }

    #[Test]
    public function it_can_create_unsubscribe_consumer_status_settled_for_authorize_merchant(): void
    {
        Queue::fake()->except(AuthorizeSchedulePaymentJob::class);

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

        $service = $this->mock(AuthorizePaymentService::class);
        $service->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new AuthorizeTransactionResponse);

        AuthorizeSchedulePaymentJob::dispatchSync($scheduleTransaction);

        $transaction = $scheduleTransaction->consumer->transactions()->first();

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => Cache::get('transaction_id'),
            'transaction_type' => TransactionType::INSTALLMENT->value,
            'consumer_id' => $scheduleTransaction->consumer_id,
            'company_id' => $scheduleTransaction->consumer->company_id,
            'payment_profile_id' => $scheduleTransaction->consumer->paymentProfile->id,
            'payment_mode' => $scheduleTransaction->consumer->paymentProfile->method,
            'subclient_id' => $scheduleTransaction->consumer->subclient_id,
            'rnn_invoice_id' => 9000,
            'superadmin_process' => false,
            'amount' => $amount = number_format((float) $scheduleTransaction->amount, 2, thousands_separator: ''),
            'rnn_share' => $ynShare = number_format((float) ($amount * $this->fee / 100), 2, thousands_separator: ''),
            'company_share' => number_format((float) ($amount - $ynShare), 2, thousands_separator: ''),
            'status' => TransactionStatus::SUCCESSFUL->value,
            'status_code' => 201,
        ]);

        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertNotNull($scheduleTransaction->last_attempted_at);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $scheduleTransaction->status);
        $this->assertEquals($transaction->id, $scheduleTransaction->transaction_id);

        $this->assertEquals(ConsumerStatus::SETTLED, $scheduleTransaction->consumer->refresh()->status);
        $this->assertEquals(false, $scheduleTransaction->consumer->has_failed_payment);
        $this->assertEquals(0, $scheduleTransaction->consumer->current_balance);

        $this->assertEquals(0, $scheduleTransaction->consumer->consumerNegotiation->refresh()->payment_plan_current_balance);

        Queue::assertNothingPushed();
    }

    private function prepareMerchant(): ScheduleTransaction
    {
        $merchant = Merchant::factory()->create(['merchant_name' => MerchantName::AUTHORIZE]);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state(['text_permission' => true, 'email_permission' => true]))
            ->has(PaymentProfile::factory()->for($merchant))
            ->create([
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'offer_accepted' => true,
            ]);

        CompanyMembership::factory()
            ->for($consumer->company)
            ->for(Membership::factory()->create(['fee' => $this->fee]))
            ->create(['current_plan_end' => now()]);

        ConsumerNegotiation::factory()->create([
            'consumer_id' => $consumer->id,
            'payment_plan_current_balance' => $consumer->current_balance - fake()->randomFloat(2, 1, 500), // Discounted Price
        ]);

        return ScheduleTransaction::factory()
            ->create([
                'transaction_type' => fake()->randomElement([TransactionType::INSTALLMENT, TransactionType::PIF]),
                'consumer_id' => $consumer->id,
                'status' => TransactionStatus::SCHEDULED,
                'attempt_count' => 1,
                'schedule_date' => now()->subWeek()->toDateString(),
                'amount' => $consumer->current_balance,
                'revenue_share_percentage' => $this->fee,
            ]);
    }
}

class AuthorizeTransactionResponse
{
    public function getTransactionResponse(): TransactionResponse
    {
        return new TransactionResponse;
    }

    public function getMessages(): TransactionResponse
    {
        return new TransactionResponse;
    }
}

class TransactionResponse
{
    public function getMessages(): array
    {
        return [fake()->sentence()];
    }

    public function getTransId(): string
    {
        Cache::put('transaction_id', $transactionId = fake()->uuid());

        return $transactionId;
    }

    public function getResponseCode(): int
    {
        return 201;
    }

    public function getResultCode(): string
    {
        return 'Ok';
    }
}

class FailedAuthorizeTransactionResponse
{
    public function getTransactionResponse(): FailedTransactionResponse
    {
        return new FailedTransactionResponse;
    }

    public function getMessages(): FailedTransactionResponse
    {
        return new FailedTransactionResponse;
    }
}

class FailedTransactionResponse extends TransactionResponse
{
    public function getResponseCode(): int
    {
        return 403;
    }

    public function getResultCode(): string
    {
        return 'Not Ok';
    }

    public function getErrors(): array
    {
        return [
            new class {
                public function getErrorCode(): int
                {
                    return 502;
                }
            },
        ];
    }
}
