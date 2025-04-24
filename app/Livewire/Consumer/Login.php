<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Livewire\Consumer\Forms\LoginForm;
use App\Services\ConsumerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.guest-layout')]
class Login extends Component
{
    public LoginForm $form;

    public bool $resetCaptcha = false;

    public function authenticate(): void
    {
        $this->resetCaptcha = true;

        $validatedData = $this->form->validate();

        $this->form->authenticate();

        Cache::forget('personalized-logo');

        app(ConsumerService::class)->updateCampaignTrackerClickCount($validatedData);

        $this->success(__('Logged in.'));

        $this->redirectRoute('consumer.account', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.consumer.login')->title(__('Login'));
    }
}
