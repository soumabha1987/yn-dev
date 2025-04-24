<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\InstallmentType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\ScheduleTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SkipScheduleTransactionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ScheduleTransaction $scheduleTransaction,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lastScheduledTransaction = ScheduleTransaction::query()
            ->where('transaction_type', TransactionType::INSTALLMENT)
            ->where('consumer_id', $this->scheduleTransaction->consumer_id)
            ->where('company_id', $this->scheduleTransaction->company_id)
            ->where('subclient_id', $this->scheduleTransaction->subclient_id)
            ->where('status', TransactionStatus::SCHEDULED)
            ->where('schedule_date', '>', $this->scheduleTransaction->schedule_date)
            ->latest('schedule_date')
            ->first();

        /** @var InstallmentType $installment */
        $installment = $this->scheduleTransaction->consumer->consumerNegotiation->installment_type;

        /** @var Carbon $lastScheduledDate */
        $lastScheduledDate = $lastScheduledTransaction->schedule_date;

        $skipScheduleTo = match ($installment) {
            InstallmentType::MONTHLY => $lastScheduledDate->isSameDay($lastScheduledDate->endOfMonth())
                ? $lastScheduledDate->addMonthNoOverflow()->endOfMonth()
                : $lastScheduledDate->addMonthNoOverflow(),
            InstallmentType::BIMONTHLY => $lastScheduledTransaction->schedule_date->addBimonthly(),
            InstallmentType::WEEKLY => $lastScheduledDate->addWeek(),
        };

        $this->scheduleTransaction->update([
            'previous_schedule_date' => $this->scheduleTransaction->schedule_date,
            'schedule_date' => $skipScheduleTo->toDateString(),
            'status' => TransactionStatus::SCHEDULED->value,
            'status_code' => null,
        ]);
    }
}
