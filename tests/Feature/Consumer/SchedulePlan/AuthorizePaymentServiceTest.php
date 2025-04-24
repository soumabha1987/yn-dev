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
use App\Models\Subclient;
use App\Models\Transaction;
use App\Services\Consumer\AuthorizePaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class AuthorizePaymentServiceTest extends TestCase
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
                'merchant_name' => MerchantName::AUTHORIZE->value,
                'merchant_type' => MerchantType::CC,
            ]);

        $this->consumer->paymentProfile->update(['merchant_id' => $merchant->id]);

        $this->scheduleTransaction = ScheduleTransaction::factory()
            ->for($company)
            ->for($this->consumer->subclient)
            ->for($this->consumer)
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'last_attempted_at' => null,
                'attempt_count' => 0,
            ]);

        CompanyMembership::factory()
            ->for(Membership::factory()->create(['fee' => $this->fee = fake()->numberBetween(0, 50)]))
            ->for($this->consumer->company)
            ->create();

        $this->communicationStatus = CommunicationStatus::factory()->create([
            'code' => CommunicationCode::BALANCE_PAID->value,
            'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
        ]);
    }

    #[Test]
    public function it_can_successfully_pay_the_remaining_amount_with_a_successful_response(): void
    {
        Mail::fake();

        $this->scheduleTransaction->update(['amount' => $amount = 50.36]);
        $this->consumer->update(['current_balance' => 50.35]);
        $this->consumer->consumerNegotiation()->update([
            'payment_plan_current_balance' => (float) $this->consumer->current_balance - fake()->randomFloat(2, 1, 500), // Discounted Price
        ]);

        $this->partialMock(AuthorizePaymentService::class)
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new SuccessfulPayment);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payRemainingAmount')
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $transaction = Transaction::firstOrFail();

        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);
        $this->assertEquals(0.0, $this->consumer->current_balance);
        $this->assertFalse($this->consumer->has_failed_payment);

        $this->assertEquals($transaction->id, $this->scheduleTransaction->refresh()->transaction_id);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $this->scheduleTransaction->status);
        $this->assertEquals(1, $this->scheduleTransaction->attempt_count);
        $this->assertNotNull($this->scheduleTransaction->last_attempted_at);

        $this->assertEquals('0', $this->consumer->consumerNegotiation->refresh()->payment_plan_current_balance);

        $this->assertEquals(TransactionStatus::SUCCESSFUL, $transaction->status);
        $this->assertEquals(Cache::pull('transaction_id'), $transaction->transaction_id);
        $this->assertEquals(TransactionType::PARTIAL_PIF, $transaction->transaction_type);
        $this->assertEquals((string) $this->consumer->id, $transaction->consumer_id);
        $this->assertEquals((string) $this->consumer->company_id, $transaction->company_id);
        $this->assertEquals($this->consumer->subclient_id, $transaction->subclient_id);
        $this->assertEquals((string) $amount, $transaction->amount);

        $ynShare = number_format($amount * $this->fee / 100, 2, thousands_separator: '');
        $this->assertEquals($ynShare, $transaction->rnn_share);

        $this->assertEquals(number_format($amount - $ynShare, 2, thousands_separator: ''), $transaction->company_share);
        $this->assertEquals([], $transaction->gateway_response);
        $this->assertEquals((string) $this->consumer->paymentProfile->id, $transaction->payment_profile_id);
        $this->assertEquals(9000, $transaction->rnn_invoice_id);
        $this->assertFalse($transaction->superadmin_process);
        $this->assertEquals($this->consumer->paymentProfile->method->value, $transaction->payment_mode);
        $this->assertEquals('201', $transaction->status_code);

        Mail::assertQueued(
            AutomatedTemplateMail::class,
            fn (AutomatedTemplateMail $mail) => $this->consumer->is((fn () => $this->{'consumer'})->call($mail))
        );
    }

    #[Test]
    public function it_can_successfully_pay_the_installment_amount_with_a_successful_response(): void
    {
        Mail::fake();

        $this->scheduleTransaction->update(['amount' => $amount = 50.36]);
        $this->consumer->update(['current_balance' => 50.35]);
        $this->consumer->consumerNegotiation->update([
            'payment_plan_current_balance' => (float) $this->consumer->current_balance - fake()->randomFloat(2, 1, 500), // Discounted Price
        ]);

        $this->partialMock(AuthorizePaymentService::class)
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new SuccessfulPayment);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payInstallmentAmount', $this->scheduleTransaction)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $transaction = Transaction::firstOrFail();

        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);
        $this->assertEquals(0.0, $this->consumer->current_balance);
        $this->assertFalse($this->consumer->has_failed_payment);
        $this->assertEquals($transaction->id, $this->scheduleTransaction->refresh()->transaction_id);
        $this->assertEquals(TransactionStatus::SUCCESSFUL, $this->scheduleTransaction->status);
        $this->assertEquals(1, $this->scheduleTransaction->attempt_count);
        $this->assertNotNull($this->scheduleTransaction->last_attempted_at);
        $this->consumer->consumerNegotiation->refresh();
        $this->assertEquals('0', $this->consumer->consumerNegotiation->payment_plan_current_balance);

        $this->assertEquals(TransactionStatus::SUCCESSFUL, $transaction->status);
        $this->assertEquals(Cache::pull('transaction_id'), $transaction->transaction_id);
        $this->assertEquals(TransactionType::INSTALLMENT, $transaction->transaction_type);
        $this->assertEquals((string) $this->consumer->id, $transaction->consumer_id);
        $this->assertEquals((string) $this->consumer->company_id, $transaction->company_id);
        $this->assertEquals($this->consumer->subclient_id, $transaction->subclient_id);
        $this->assertEquals((string) $amount, $transaction->amount);

        $ynShare = number_format($amount * $this->fee / 100, 2, thousands_separator: '');
        $this->assertEquals($ynShare, $transaction->rnn_share);
        $this->assertEquals(number_format($amount - $ynShare, 2, thousands_separator: ''), $transaction->company_share);
        $this->assertEquals([], $transaction->gateway_response);
        $this->assertEquals((string) $this->consumer->paymentProfile->id, $transaction->payment_profile_id);
        $this->assertEquals(9000, $transaction->rnn_invoice_id);
        $this->assertFalse($transaction->superadmin_process);
        $this->assertEquals($this->consumer->paymentProfile->method->value, $transaction->payment_mode);
        $this->assertEquals('201', $transaction->status_code);

        Mail::assertQueued(
            AutomatedTemplateMail::class,
            fn (AutomatedTemplateMail $mail) => $this->consumer->is((fn () => $this->{'consumer'})->call($mail))
        );
    }

    #[Test]
    public function it_can_pay_the_remaining_amount_and_return_a_failed_response(): void
    {
        $this->partialMock(AuthorizePaymentService::class)
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new FailedPayment);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payRemainingAmount')
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertTrue($this->consumer->refresh()->has_failed_payment);
        $transaction = Transaction::firstOrFail();
        $this->assertEquals(Cache::pull('transaction_id'), $transaction->transaction_id);
        $this->assertEquals(TransactionStatus::FAILED, $transaction->status);
        $this->assertEquals(TransactionType::PARTIAL_PIF, $transaction->transaction_type);
        $this->assertEquals((string) $this->consumer->id, $transaction->consumer_id);
        $this->assertEquals((string) $this->consumer->company_id, $transaction->company_id);
        $this->assertEquals($this->consumer->subclient_id, $transaction->subclient_id);
        $this->assertNull($transaction->rnn_share);
        $this->assertNull($transaction->company_share);
        $this->assertEquals([], $transaction->gateway_response);
        $this->assertEquals((string) $this->consumer->paymentProfile->id, $transaction->payment_profile_id);
        $this->assertEquals(9000, $transaction->rnn_invoice_id);
        $this->assertFalse($transaction->superadmin_process);
        $this->assertEquals('502', $transaction->status_code);
    }

    #[Test]
    public function it_can_pay_installment_amount_and_return_failed_response(): void
    {
        $this->partialMock(AuthorizePaymentService::class)
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn(new FailedPayment);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payInstallmentAmount')
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertTrue($this->consumer->refresh()->has_failed_payment);
        $transaction = Transaction::firstOrFail();
        $this->assertEquals(Cache::pull('transaction_id'), $transaction->transaction_id);
        $this->assertEquals(TransactionStatus::FAILED, $transaction->status);
        $this->assertEquals(TransactionType::INSTALLMENT, $transaction->transaction_type);
        $this->assertEquals((string) $this->consumer->id, $transaction->consumer_id);
        $this->assertEquals((string) $this->consumer->company_id, $transaction->company_id);
        $this->assertEquals($this->consumer->subclient_id, $transaction->subclient_id);
        $this->assertNull($transaction->rnn_share);
        $this->assertNull($transaction->company_share);
        $this->assertEquals([], $transaction->gateway_response);
        $this->assertEquals((string) $this->consumer->paymentProfile->id, $transaction->payment_profile_id);
        $this->assertEquals(9000, $transaction->rnn_invoice_id);
        $this->assertFalse($transaction->superadmin_process);
        $this->assertEquals('502', $transaction->status_code);
    }
}

class SuccessfulPayment
{
    public function getTransactionResponse(): self
    {
        return $this;
    }

    public function getMessages()
    {
        return new class {
            public function getMessages(): array
            {
                return [fake()->sentence()];
            }

            public function getResultCode(): string
            {
                return 'Ok';
            }
        };
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
}

class FailedPayment
{
    public function getTransactionResponse(): self
    {
        return $this;
    }

    public function getMessages()
    {
        return new class {
            public function getMessages(): array
            {
                return [fake()->sentence()];
            }

            public function getResultCode(): string
            {
                return 'Not Ok';
            }
        };
    }

    public function getTransId(): string
    {
        Cache::put('transaction_id', $transactionId = fake()->uuid());

        return $transactionId;
    }

    public function getResponseCode(): int
    {
        return 403;
    }

    public function getErrors(): array
    {
        return [
            new class {
                public function getErrorCode()
                {
                    return 502;
                }
            },
        ];
    }
}
