<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\TransactionStatus;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\ScheduleTransaction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class NotifyUpcomingPaymentReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:upcoming-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a payment reminder 5 days and 1 day before the due date.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        ScheduleTransaction::query()
            ->with('consumer', function (BelongsTo $relation): void {
                $relation->where('status', ConsumerStatus::PAYMENT_ACCEPTED)
                    ->with(['consumerProfile', 'unsubscribe', 'company', 'subclient']);
            })
            ->where(function (Builder $query): void {
                $query->where('schedule_date', today()->addDays(5)->toDateString())
                    ->orWhere('schedule_date', today()->addDay()->toDateString());
            })
            ->where('attempt_count', 0)
            ->where('status', TransactionStatus::SCHEDULED->value)
            ->whereNotNull('payment_profile_id')
            ->chunkById(100, function (Collection $upcomingScheduleTransactions): void {
                $upcomingScheduleTransactions->each(function (ScheduleTransaction $scheduleTransaction) {
                    $communicationCode = match ($scheduleTransaction->schedule_date->toDateString()) {
                        today()->addDays(5)->toDateString() => CommunicationCode::FIVE_DAYS_UPCOMING_PAYMENT_REMINDER,
                        today()->addDay()->toDateString() => CommunicationCode::ONE_DAY_UPCOMING_PAYMENT_REMINDER,
                        default => null,
                    };

                    TriggerEmailAndSmsServiceJob::dispatchIf($communicationCode !== null, $scheduleTransaction->consumer, $communicationCode);
                });
            });
    }
}
