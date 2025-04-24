<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\WithPagination as LivewireWithPagination;

trait WithPagination
{
    use LivewireWithPagination;

    public int $perPage = 10;

    public function mountWithPagination(): void
    {
        $this->perPage = Session::get($this->getPerPageSessionKey(), 10);
    }

    public function updatedPerPage(): void
    {
        Session::put($this->getPerPageSessionKey(), $this->perPage);

        $this->resetPage();
    }

    private function getPerPageSessionKey(): string
    {
        $userId = Auth::id();
        $className = Str::replace('\\', '_', self::class);

        return "tables.{$userId}_{$className}_per_page";
    }
}
