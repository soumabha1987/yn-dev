<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\MyAccount;

use App\Enums\ConsumerStatus;
use App\Livewire\Consumer\Forms\HoldForm;
use App\Models\Consumer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Hold extends Component
{
    public Consumer $consumer;

    public HoldForm $form;

    public string $page = '';

    public function mount(): void
    {
        $this->form->init($this->consumer);
    }

    public function hold(): void
    {
        $validateData = $this->form->validate();

        $this->consumer->update([...$validateData, 'status' => ConsumerStatus::HOLD]);

        $this->success(__('Your account plan has been successfully placed on hold.'));

        $this->dispatch('close-dialog');
    }

    public function render(): View
    {
        return view('livewire.consumer.my-account.hold');
    }
}
