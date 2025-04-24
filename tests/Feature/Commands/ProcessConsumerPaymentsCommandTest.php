<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\ProcessConsumerPaymentsCommand;
use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\MerchantName;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Jobs\AuthorizeSchedulePaymentJob;
use App\Jobs\SkipScheduleTransactionJob;
use App\Jobs\StripeSchedulePaymentJob;
use App\Jobs\TilledSchedulePaymentJob;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Jobs\USAEpaySchedulePaymentJob;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessConsumerPaymentsCommandTest extends TestCase
{
    #[Test]
    public function it_can_not_process_consumer_payment_if_it_is_not_scheduled(): void
    {
        ScheduleTransaction::factory()->create([
            'status' => TransactionStatus::SCHEDULED,
            'attempt_count' => 0,
        ]);

        $this->artisan(ProcessConsumerPaymentsCommand::class)->assertOk();
    }

    #[Test]
    public function it_can_process_consumer_payment_of_authorize_merchant(): void
    {
        Queue::fake();

        $scheduleTransaction = $this->prepareMerchant(MerchantName::AUTHORIZE);

        $this->artisan(ProcessConsumerPaymentsCommand::class)->assertOk();

        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertEquals(now()->toDateString(), $scheduleTransaction->last_attempted_at->toDateString());

        Queue::assertPushed(AuthorizeSchedulePaymentJob::class);
    }

    #[Test]
    public function it_can_process_consumer_payment_of_stripe_merchant(): void
    {
        Queue::fake();

        $scheduleTransaction = $this->prepareMerchant(MerchantName::STRIPE);

        $this->artisan(ProcessConsumerPaymentsCommand::class)->assertOk();

        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertEquals(now()->toDateString(), $scheduleTransaction->last_attempted_at->toDateString());

        Queue::assertPushed(StripeSchedulePaymentJob::class);
    }

    #[Test]
    public function it_can_process_consumer_payment_of_usa_epay_merchant(): void
    {
        Queue::fake();

        $scheduleTransaction = $this->prepareMerchant(MerchantName::USA_EPAY);

        $this->artisan(ProcessConsumerPaymentsCommand::class)->assertOk();

        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertEquals(now()->toDateString(), $scheduleTransaction->last_attempted_at->toDateString());

        Queue::assertPushed(USAEpaySchedulePaymentJob::class);
    }

    #[Test]
    public function it_can_process_consumer_payment_of_tilled_merchant(): void
    {
        Queue::fake();

        $scheduleTransaction = $this->prepareMerchant(MerchantName::YOU_NEGOTIATE);

        $this->artisan(ProcessConsumerPaymentsCommand::class)->assertOk();

        $this->assertEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertEquals(now()->toDateString(), $scheduleTransaction->last_attempted_at->toDateString());

        Queue::assertPushed(TilledSchedulePaymentJob::class);
    }

    #[Test]
    public function it_can_send_failed_payment_email_to_consumer(): void
    {
        Queue::fake()->except(TilledSchedulePaymentJob::class);

        Http::fake(fn () => Http::response(['Not allow'], Response::HTTP_NOT_FOUND));

        $scheduleTransaction = $this->prepareMerchant(MerchantName::YOU_NEGOTIATE);

        $this->artisan(ProcessConsumerPaymentsCommand::class)->assertOk();

        $this->assertEquals(TransactionStatus::FAILED, $scheduleTransaction->refresh()->status);
        $this->assertEquals(1, $scheduleTransaction->attempt_count);
        $this->assertEquals(now()->toDateString(), $scheduleTransaction->last_attempted_at->toDateString());

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

    private function prepareMerchant(MerchantName $merchantName): ScheduleTransaction
    {
        $merchant = Merchant::factory()->create(['merchant_name' => $merchantName]);

        $consumerProfile = ConsumerProfile::factory()->create([
            'text_permission' => true,
            'email_permission' => true,
        ]);

        $consumer = Consumer::factory()
            ->has(PaymentProfile::factory()->for($merchant))
            ->create([
                'consumer_profile_id' => $consumerProfile->id,
                'status' => ConsumerStatus::PAYMENT_ACCEPTED->value,
                'offer_accepted' => true,
            ]);

        return ScheduleTransaction::factory()
            ->create([
                'transaction_type' => fake()->randomElement([TransactionType::INSTALLMENT, TransactionType::PIF]),
                'consumer_id' => $consumer->id,
                'status' => TransactionStatus::SCHEDULED->value,
                'attempt_count' => 0,
                'schedule_date' => now()->subWeek()->toDateString(),
            ]);
    }

    #[Test]
    public function it_can_process_consumer_status_is_on_hold(): void
    {
        Queue::fake();

        $scheduleTransaction = $this->prepareMerchant(MerchantName::STRIPE);

        $scheduleTransaction->consumer->update(['status' => ConsumerStatus::HOLD]);

        $this->artisan(ProcessConsumerPaymentsCommand::class)->assertOk();

        Queue::assertPushed(SkipScheduleTransactionJob::class);

        $this->assertNotEquals(1, $scheduleTransaction->refresh()->attempt_count);
        $this->assertNotEquals(now()->toDateString(), $scheduleTransaction->last_attempted_at->toDateString());

        Queue::assertNotPushed(StripeSchedulePaymentJob::class);
    }
}
