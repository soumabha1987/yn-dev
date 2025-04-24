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
use App\Services\Consumer\USAEpayPaymentService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class USAEpayPaymentServiceTest extends TestCase
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
                'merchant_name' => MerchantName::USA_EPAY->value,
                'merchant_type' => MerchantType::CC,
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
    }

    #[Test]
    public function it_can_successfully_pay_the_remaining_amount_with_a_successful_response(): void
    {
        Mail::fake();

        $this->totalAmount = 0;
        collect(range(1, 10))->each(function () {
            ScheduleTransaction::factory()
                ->create([
                    'transaction_id' => null,
                    'consumer_id' => $this->consumer->id,
                    'company_id' => $this->consumer->company_id,
                    'payment_profile_id' => $this->consumer->paymentProfile->id,
                    'schedule_date' => $this->scheduleDate = fake()->date(),
                    'amount' => $this->scheduleAmount = $amount = fake()->numberBetween(1, 100),
                    'status' => TransactionStatus::SCHEDULED->value,
                ]);
            $this->totalAmount += $amount;
        });

        $transactionId = fake()->uuid();

        $this->partialMock(USAEpayPaymentService::class)
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn((object) [
                'RefNum' => $transactionId,
                'ResultCode' => 'A',
                'Result' => 'success',
            ]);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payRemainingAmount')
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $transaction = $this->consumer->transactions->first();

        $this->assertDatabaseMissing(ScheduleTransaction::class, [
            'status' => TransactionStatus::SCHEDULED,
            'transaction_id' => null,
        ]);

        $this->assertDatabaseHas(ScheduleTransaction::class, ['transaction_id' => $transaction->id]);

        $this->assertEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => $transactionId,
            'status' => TransactionStatus::SUCCESSFUL,
            'transaction_type' => TransactionType::PARTIAL_PIF,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'payment_profile_id' => $this->consumer->paymentProfile->id,
            'amount' => $this->totalAmount,
            'rnn_share' => $ynShare = number_format($this->totalAmount * $this->fee / 100, 2, thousands_separator: ''),
            'company_share' => number_format($this->totalAmount - $ynShare, 2, thousands_separator: ''),
            'processing_charges' => null,
            'gateway_response->RefNum' => $transactionId,
            'gateway_response->ResultCode' => 'A',
            'gateway_response->Result' => 'success',
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
                    'payment_profile_id' => $this->consumer->paymentProfile->id,
                    'schedule_date' => $this->scheduleDate = fake()->date(),
                    'amount' => $this->scheduleAmount = $amount = fake()->numberBetween(1, 100),
                    'status' => TransactionStatus::SCHEDULED->value,
                ]);
            $this->totalAmount += $amount;
        });

        $transactionId = fake()->uuid();

        $this->partialMock(USAEpayPaymentService::class)
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn((object) [
                'RefNum' => $transactionId,
                'ResultCode' => 'B',
                'Result' => 'failed',
            ]);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payRemainingAmount')
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertDatabaseMissing(ScheduleTransaction::class, ['status' => TransactionStatus::SUCCESSFUL]);

        $this->assertNotEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);

        $this->assertDatabaseHas(Transaction::class, [
            'transaction_id' => null,
            'status' => TransactionStatus::FAILED,
            'transaction_type' => TransactionType::PARTIAL_PIF,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'payment_profile_id' => $this->consumer->paymentProfile->id,
            'amount' => $this->totalAmount,
            'rnn_share' => null,
            'company_share' => null,
            'processing_charges' => null,
            'gateway_response->RefNum' => $transactionId,
            'gateway_response->ResultCode' => 'B',
            'gateway_response->Result' => 'failed',
            'status_code' => 'B',
        ]);
    }

    #[Test]
    public function it_can_successfully_pay_the_installment_amount_with_a_successful_response(): void
    {
        Mail::fake();

        $this->scheduleTransaction = ScheduleTransaction::factory()
            ->create([
                'transaction_id' => null,
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'subclient_id' => $this->consumer->subclient_id,
                'payment_profile_id' => $this->consumer->paymentProfile->id,
                'schedule_date' => $this->scheduleDate = fake()->date(),
                'amount' => $this->scheduleAmount = $this->amount = fake()->numberBetween(1, 100),
                'status' => TransactionStatus::SCHEDULED->value,
            ]);

        $transactionId = fake()->uuid();

        $this->partialMock(USAEpayPaymentService::class)
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn((object) [
                'RefNum' => $transactionId,
                'ResultCode' => 'A',
                'Result' => 'success',
            ]);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payInstallmentAmount', $this->scheduleTransaction)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $transaction = $this->consumer->transactions->first();

        $this->assertEquals(TransactionStatus::SUCCESSFUL, $this->scheduleTransaction->status);
        $this->assertEquals($transaction->id, $this->scheduleTransaction->transaction_id);

        $this->assertDatabaseHas(Transaction::class, [
            'id' => $transaction->id,
            'transaction_id' => $transactionId,
            'status' => TransactionStatus::SUCCESSFUL,
            'transaction_type' => TransactionType::INSTALLMENT,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'payment_profile_id' => $this->consumer->paymentProfile->id,
            'amount' => $this->amount,
            'rnn_share' => $ynShare = number_format($this->amount * $this->fee / 100, 2, thousands_separator: ''),
            'company_share' => number_format($this->amount - $ynShare, 2, thousands_separator: ''),
            'processing_charges' => null,
            'gateway_response->RefNum' => $transactionId,
            'gateway_response->ResultCode' => 'A',
            'gateway_response->Result' => 'success',
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
            ]);

        $transactionId = fake()->uuid();

        $this->partialMock(USAEpayPaymentService::class)
            ->shouldReceive('proceedPayment')
            ->atLeast()
            ->once()
            ->withAnyArgs()
            ->andReturn((object) [
                'RefNum' => $transactionId,
                'ResultCode' => 'B',
                'Result' => 'failed',
            ]);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payInstallmentAmount', $this->scheduleTransaction)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertEquals(TransactionStatus::SCHEDULED, $this->scheduleTransaction->refresh()->status);

        $this->assertNotEquals(ConsumerStatus::SETTLED, $this->consumer->refresh()->status);

        $this->assertDatabaseHas(Transaction::class, [
            'transaction_id' => null,
            'status' => TransactionStatus::FAILED,
            'transaction_type' => TransactionType::INSTALLMENT,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'payment_profile_id' => $this->consumer->paymentProfile->id,
            'amount' => $this->amount,
            'rnn_share' => null,
            'company_share' => null,
            'processing_charges' => null,
            'gateway_response->RefNum' => $transactionId,
            'gateway_response->ResultCode' => 'B',
            'gateway_response->Result' => 'failed',
            'status_code' => 'B',
        ]);
    }
}
