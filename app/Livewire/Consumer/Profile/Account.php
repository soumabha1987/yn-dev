<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Profile;

use App\Livewire\Consumer\Forms\Profile\AccountForm;
use App\Models\Consumer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class Account extends Component
{
    public AccountForm $form;

    private Consumer $consumer;

    public function __construct()
    {
        $this->consumer = Auth::guard('consumer')->user();
    }

    public function mount(): void
    {
        $this->form->init($this->consumer);
    }

    public function updateProfile(): void
    {
        $validatedData = $this->form->validate();

        $this->consumer->consumerProfile()->update($validatedData);

        $this->success(__('Profile updated.'));
    }

    public function render(): View
    {
        return view('livewire.consumer.profile.account');
    }
}
