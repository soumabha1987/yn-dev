<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageConsumers\ConsumerProfile;

use App\Enums\TransactionStatus;
use App\Livewire\Traits\WithPagination;
use App\Models\Consumer;
use App\Services\ScheduleTransactionService;
use App\Services\TransactionService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportPagination\WithoutUrlPagination;

class Transactions extends Component
{
    use WithoutUrlPagination;
    use WithPagination;

    public Consumer $consumer;

    public string $schedule_date;

    public function mount(): void
    {
        $this->schedule_date = now()->toDateString();
    }

    public function reschedule(int $transactionId): void
    {
        $validatedData = $this->validate([
            'schedule_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:now'],
        ]);

        $scheduleTransaction = app(ScheduleTransactionService::class)->findByTransaction($transactionId);

        if ($scheduleTransaction) {
            $rescheduleTransaction = $scheduleTransaction->replicate(['last_attempted_at', 'attempt_count'])
                ->fill([
                    'schedule_date' => $validatedData['schedule_date'],
                    'status' => TransactionStatus::SCHEDULED,
                    'previous_schedule_date' => $scheduleTransaction->schedule_date,
                ]);

            $rescheduleTransaction->save();

            $scheduleTransaction->update(['status' => TransactionStatus::RESCHEDULED]);

            $this->success(__('Your payment is rescheduled, you will get email notification once payment processed'));
        } else {
            $this->error(__('You can\'t reschedule this payment'));
        }

        $this->dispatch('close-dialog');
    }

    public function render(): View
    {
        return view('livewire.creditor.manage-consumers.consumer-profile.transactions')
            ->with('transactions', app(TransactionService::class)->fetchHistory($this->consumer->id, $this->perPage));
    }
}
