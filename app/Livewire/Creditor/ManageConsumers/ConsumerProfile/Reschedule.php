<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageConsumers\ConsumerProfile;

use App\Enums\TransactionStatus;
use App\Models\ScheduleTransaction;

trait Reschedule
{
    public string $schedule_date = '';

    public function mountReschedule(): void
    {
        $this->schedule_date = now()->toDateString();
    }

    public function reschedule(ScheduleTransaction $scheduleTransaction): void
    {
        $validatedData = $this->validate([
            'schedule_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:now'],
        ]);

        $scheduleTransaction
            ->replicate(['last_attempted_at', 'attempt_count'])
            ->fill([
                'schedule_date' => $validatedData['schedule_date'],
                'status' => TransactionStatus::SCHEDULED,
                'previous_schedule_date' => $scheduleTransaction->schedule_date,
            ])
            ->save();

        $scheduleTransaction->update(['status' => TransactionStatus::CREDITOR_CHANGE_DATE]);

        $this->success(__('Your payment is rescheduled, you will get email notification once payment processed'));

        $this->dispatch('close-dialog');
    }
}
