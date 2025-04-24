<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

trait Logout
{
    public function logout(): void
    {
        Auth::logout();

        Session::invalidate();

        Session::regenerateToken();

        Cache::flush();

        $this->js('localStorage.clear()');

        $this->success(__('Logged out. Have a great day and see you soon to knock out debt.'));

        $this->redirectIntended(navigate: true);
    }
}
