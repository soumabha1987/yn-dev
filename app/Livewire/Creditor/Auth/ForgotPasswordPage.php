<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Auth;

use App\Livewire\Creditor\Forms\Auth\ForgotPasswordForm;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.guest-layout')]
class ForgotPasswordPage extends Component
{
    public ForgotPasswordForm $form;

    public bool $resetCaptcha = false;

    public function mount(): void
    {
        $this->form->email = Session::get('entered_user_email', '');
    }

    public function forgotPassword(): void
    {
        $this->resetCaptcha = true;

        $validatedData = $this->form->validate();

        $status = Password::sendResetLink(['email' => $validatedData['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->form->addError('email', __($status));

            return;
        }

        $this->form->reset();

        $this->success(__($status));
    }

    public function render(): View
    {
        return view('livewire.creditor.auth.forgot-password-page')->title(__('Forgot Password'));
    }
}
