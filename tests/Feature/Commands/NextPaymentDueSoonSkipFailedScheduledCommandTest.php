<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\NextPaymentDueSoonSkipFailedScheduledCommand;
use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Enums\MerchantName;
use App\Enums\MerchantType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Jobs\SkipScheduleTransactionJob;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ConsumerProfile;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NextPaymentDueSoonSkipFailedScheduledCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();
    }

    #[Test]
    public function scheduled_transactions_to_be_skipped(): void
    {
        Queue::fake();

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state(['text_permission' => true, 'email_permission' => true]))
            ->for(Company::factory())
            ->has(ConsumerNegotiation::factory()->state([
                'active_negotiation' => true,
                'installment_type' => InstallmentType::WEEKLY,
            ]))
            ->create([
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'offer_accepted' => true,
                'subclient_id' => null,
            ]);

        $scheduleTransactionA = ScheduleTransaction::factory()
            ->recycle($consumer)
            ->for(
                $paymentProfile = PaymentProfile::factory()
                    ->for(
                        Merchant::factory()->state([
                            'merchant_name' => MerchantName::AUTHORIZE,
                            'merchant_type' => MerchantType::CC,
                            'verified_at' => now(),
                        ])
                    )
                    ->state(['method' => MerchantType::CC])
            )
            ->create([
                'last_attempted_at' => now()->subDays(5),
                'transaction_type' => TransactionType::INSTALLMENT,
                'schedule_date' => now()->subWeek()->toDateString(),
                'status' => TransactionStatus::FAILED,
                'attempt_count' => 2,
            ]);

        ScheduleTransaction::factory()
            ->recycle($paymentProfile)
            ->create([
                'company_id' => $scheduleTransactionA->company_id,
                'consumer_id' => $scheduleTransactionA->consumer_id,
                'subclient_id' => $scheduleTransactionA->subclient_id,
                'last_attempted_at' => null,
                'transaction_type' => TransactionType::INSTALLMENT,
                'schedule_date' => now()->subDays(5)->toDateString(),
                'status' => TransactionStatus::SCHEDULED,
                'attempt_count' => 2,
            ]);

        ScheduleTransaction::factory()
            ->recycle($consumer)
            ->create([
                'company_id' => $scheduleTransactionA->company_id,
                'consumer_id' => $scheduleTransactionA->consumer_id,
                'subclient_id' => $scheduleTransactionA->subclient_id,
                'last_attempted_at' => null,
                'transaction_type' => TransactionType::INSTALLMENT,
                'schedule_date' => now()->addDays(2)->addWeek()->toDateString(),
                'status' => TransactionStatus::SCHEDULED->value,
                'attempt_count' => 2,
            ]);

        $this->artisan(NextPaymentDueSoonSkipFailedScheduledCommand::class)
            ->assertExitCode(Command::SUCCESS)
            ->assertOk();

        Queue::assertPushed(SkipScheduleTransactionJob::class);

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::PAYMENT_FAILED_MOVE_TO_SKIP
        );
    }
}
