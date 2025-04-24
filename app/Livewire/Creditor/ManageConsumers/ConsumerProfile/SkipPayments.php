<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageConsumers\ConsumerProfile;

use App\Enums\InstallmentType;
use App\Enums\TransactionStatus;
use App\Models\ScheduleTransaction;
use App\Services\Consumer\ScheduleTransactionService;
use Carbon\Carbon;

trait SkipPayments
{
    public function skipPayment(ScheduleTransaction $scheduleTransaction): void
    {
        if ($scheduleTransaction->schedule_date->isSameDay(today())) {
            $this->dispatch('close-confirmation-box');

            return;
        }

        $this->consumer->increment('skip_schedules');

        $lastScheduleTransaction = app(ScheduleTransactionService::class)->lastScheduled($this->consumer->id);

        /** @var InstallmentType $installmentType */
        $installmentType = $this->consumer->consumerNegotiation->installment_type;

        $firstDateIsEndOfMonth = $installmentType === InstallmentType::MONTHLY
            && $lastScheduleTransaction->schedule_date->isSameDay($lastScheduleTransaction->schedule_date->endOfMonth());

        $scheduleTransaction->update([
            'previous_schedule_date' => $scheduleTransaction->schedule_date->toDateString(),
            'schedule_date' => $this->getScheduleDate($lastScheduleTransaction->schedule_date, $installmentType, $firstDateIsEndOfMonth)->toDateString(),
            'status' => TransactionStatus::SCHEDULED,
        ]);

        $this->success(__('Your payment has been skipped.'));

        $this->dispatch('close-confirmation-box');
    }

    private function getScheduleDate(Carbon $date, InstallmentType $installmentType, bool $forceEndOfMonth): Carbon
    {
        return $date->{$installmentType->getCarbonMethod()}()->when($forceEndOfMonth, fn (Carbon $date): Carbon => $date->endOfMonth());
    }
}
