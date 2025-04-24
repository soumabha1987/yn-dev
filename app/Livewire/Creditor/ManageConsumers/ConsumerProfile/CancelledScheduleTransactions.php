<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageConsumers\ConsumerProfile;

use App\Livewire\Traits\WithPagination;
use App\Models\Consumer;
use App\Services\ScheduleTransactionService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithoutUrlPagination;

class CancelledScheduleTransactions extends Component
{
    use WithoutUrlPagination;
    use WithPagination;

    public Consumer $consumer;

    public function render(): View
    {
        return view('livewire.creditor.manage-consumers.consumer-profile.cancelled-schedule-transactions')
            ->with('cancelledScheduleTransactions', app(ScheduleTransactionService::class)->fetchCancelledOfConsumer($this->consumer->id, $this->perPage));
    }
}
