<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\MyAccount;

use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RestartPlan extends Component
{
    public Consumer $consumer;

    public string $page = '';

    public function restartPlan(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
            'hold_reason' => null,
            'restart_date' => null,
        ]);

        $this->success(__('Your account plan has been successfully restarted.'));

        $this->dispatch('close-dialog');
    }

    public function render(): View
    {
        return view('livewire.consumer.my-account.restart-plan');
    }
}
