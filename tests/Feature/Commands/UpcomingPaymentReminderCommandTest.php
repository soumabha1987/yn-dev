<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\NotifyUpcomingPaymentReminder;
use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\TransactionStatus;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\ScheduleTransaction;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpcomingPaymentReminderCommandTest extends TestCase
{
    #[Test]
    public function it_can_not_send_payment_reminder_where_today_schedule_date(): void
    {
        Queue::fake();

        ScheduleTransaction::factory()->create([
            'status' => TransactionStatus::SCHEDULED,
            'attempt_count' => 0,
            'schedule_date' => now()->toDateString(),
        ]);

        $this->artisan(NotifyUpcomingPaymentReminder::class)->assertOk();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_will_send_reminder_to_consumer_for_5_days_before_upcoming_payment(): void
    {
        Queue::fake();

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state(['email_permission' => true, 'text_permission' => true]))
            ->create(['status' => ConsumerStatus::PAYMENT_ACCEPTED]);

        ScheduleTransaction::factory()
            ->for($consumer)
            ->create([
                'status' => TransactionStatus::SCHEDULED->value,
                'attempt_count' => 0,
                'schedule_date' => now()->addDays(5)->toDateString(),
            ]);

        $this->artisan(NotifyUpcomingPaymentReminder::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::FIVE_DAYS_UPCOMING_PAYMENT_REMINDER
        );
    }

    #[Test]
    public function it_will_send_reminder_for_1_Day_before_upcoming_payment(): void
    {
        Queue::fake();

        $consumer = Consumer::factory()->create([
            'consumer_profile_id' => null,
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
        ]);

        ScheduleTransaction::factory()->create([
            'consumer_id' => $consumer->id,
            'status' => TransactionStatus::SCHEDULED->value,
            'attempt_count' => 0,
            'schedule_date' => now()->addDay()->toDateString(),
        ]);

        $this->artisan(NotifyUpcomingPaymentReminder::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === CommunicationCode::ONE_DAY_UPCOMING_PAYMENT_REMINDER
        );
    }

    #[Test]
    public function it_can_not_send_payment_reminder_where_schedule_date_between_five_and_one_date(): void
    {
        Queue::fake();

        ScheduleTransaction::factory()->create([
            'status' => TransactionStatus::SCHEDULED,
            'attempt_count' => 0,
            'schedule_date' => today()->addDays(fake()->numberBetween(2, 4))->toDateString(),
        ]);

        $this->artisan(NotifyUpcomingPaymentReminder::class)->assertOk();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_will_send_reminder_for_5_days_and_1_day_before_upcoming_payment(): void
    {
        Queue::fake();

        ScheduleTransaction::factory()
            ->for(Consumer::factory()->create(['status' => ConsumerStatus::PAYMENT_ACCEPTED]))
            ->forEachSequence(
                ['schedule_date' => today()->addDay()->toDateString()],
                ['schedule_date' => today()->addDays(2)->toDateString()],
                ['schedule_date' => today()->addDays(5)->toDateString()],
            )
            ->create([
                'status' => TransactionStatus::SCHEDULED->value,
                'attempt_count' => 0,
            ]);

        $this->artisan(NotifyUpcomingPaymentReminder::class)->assertOk();

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 2);
    }
}
