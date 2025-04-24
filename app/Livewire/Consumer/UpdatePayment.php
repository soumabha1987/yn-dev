<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class UpdatePayment extends Component
{
    public function render(): View
    {
        return view('livewire.consumer.update-payment');
    }
}
