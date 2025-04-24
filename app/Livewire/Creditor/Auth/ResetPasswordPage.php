<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Auth;

use App\Livewire\Creditor\Forms\Auth\ResetPasswordForm;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.guest-layout')]
class ResetPasswordPage extends Component
{
    public ResetPasswordForm $form;

    public function mount(string $token): void
    {
        $this->form->token = $token;

        $this->form->email = request()->string('email')->toString();
    }

    public function resetPassword(): void
    {
        $this->form->validate();

        $status = Password::reset(
            $this->form->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->form->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('form.email', __($status));

            return;
        }

        $this->success(__($status));

        $this->redirectRoute('login', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.auth.reset-password-page')->title(__('Reset Password'));
    }
}
