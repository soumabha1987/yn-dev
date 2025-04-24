<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ConsumerLogs;

use App\Livewire\Traits\WithPagination;
use App\Models\Consumer;
use App\Services\ConsumerLogService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithoutUrlPagination;

class Row extends Component
{
    use WithoutUrlPagination;
    use WithPagination;

    public Consumer $consumer;

    public function mount(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $data = [
            'consumer_id' => $this->consumer->id,
            'per_page' => $this->perPage,
        ];

        return view('livewire.creditor.consumer-logs.row')
            ->with('consumerLogs', app(ConsumerLogService::class)->fetch($data));
    }
}
