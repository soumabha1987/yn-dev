<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Session;
use Livewire\Component;

class GlobalSearch extends Component
{
    #[Session('search')]
    public string $search = '';

    public function searchConsumer(): void
    {
        $this->redirectRoute('manage-consumers', ['search' => $this->search], navigate: true);
    }

    public function resetSearch(): void
    {
        $this->reset('search');

        $this->dispatch('refresh-global-search');
    }

    public function render(): View
    {
        return view('livewire.creditor.global-search');
    }
}
