<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Jobs\SkipScheduleTransactionJob;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ScheduleTransaction;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SkipScheduleTransactionJobTest extends TestCase
{
    #[Test]
    public function can_send_email_with_attachment_of_file(): void
    {
        $consumer = Consumer::factory()
            ->has(ConsumerNegotiation::factory()->state(['negotiation_type' => NegotiationType::INSTALLMENT]))
            ->create([
                'subclient_id' => null,
            ]);

        $scheduleTransactions = ScheduleTransaction::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'schedule_date' => today()->addWeeks($sequence->index + 1)->toDateString(),
            ])
            ->create([
                'consumer_id' => $consumer->id,
                'company_id' => $consumer->company_id,
                'subclient_id' => null,
                'status' => TransactionStatus::SCHEDULED,
                'transaction_type' => TransactionType::INSTALLMENT,
            ]);

        $this->assertDatabaseCount(ScheduleTransaction::class, 10);

        SkipScheduleTransactionJob::dispatch($scheduleTransactions->first());

        /** @var Carbon $lastScheduledDate */
        $lastScheduledDate = $scheduleTransactions->last()->schedule_date;

        $skipScheduleDate = match ($consumer->consumerNegotiation->installment_type) {
            InstallmentType::MONTHLY => $lastScheduledDate->isSameDay($lastScheduledDate->endOfMonth())
                ? $lastScheduledDate->addMonthNoOverflow()->endOfMonth()
                : $lastScheduledDate->addMonthNoOverflow(),
            InstallmentType::BIMONTHLY => $scheduleTransactions->last()->schedule_date->addBimonthly(),
            InstallmentType::WEEKLY => $lastScheduledDate->addWeek(),
        };

        $this->assertEquals($skipScheduleDate->toDateString(), $scheduleTransactions->first()->refresh()->schedule_date->toDateString());
    }
}
