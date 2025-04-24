<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Auth;

use App\Livewire\Creditor\Traits\Logout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.guest-layout')]
class EmailVerificationNoticePage extends Component
{
    use Logout;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user && $user->hasVerifiedEmail()) {
            Session::regenerate();

            $this->redirectIntended(navigate: true);
        }
    }

    public function resendEmailVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(navigate: true);

            return;
        }

        $user->sendEmailVerificationNotification();

        $this->success(__('Email verification link resent.'));
    }

    public function render(): View
    {
        return view('livewire.creditor.auth.email-verification-notice-page')->title(__('Verify Your Email'));
    }
}
