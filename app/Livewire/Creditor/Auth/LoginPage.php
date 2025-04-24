<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Auth;

use App\Livewire\Creditor\Forms\Auth\LoginForm;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.guest-layout')]
class LoginPage extends Component
{
    public LoginForm $form;

    public bool $resetCaptcha = false;

    public function authenticate(): void
    {
        $this->resetCaptcha = true;

        $this->form->validate();

        $this->form->authenticate();

        $this->js('localStorage.clear()');

        $this->success(__('Logged in.'));

        $this->redirectRoute('home', navigate: true);
    }

    public function forgotPassword(): void
    {
        Session::put('entered_user_email', $this->form->email);

        $this->redirectRoute('forgot-password', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.auth.login-page')->title(__('Login'));
    }
}
