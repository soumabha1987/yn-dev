<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class Logout extends Component
{
    public function logout(): void
    {
        Auth::guard('consumer')->logout();

        Session::invalidate();

        Session::regenerateToken();

        Cache::flush();

        $this->success(__('Logged out. Have a great day and see you soon to knock out debt.'));

        $this->redirectIntended(navigate: true);
    }

    public function render(): View
    {
        return view('livewire.consumer.logout');
    }
}
