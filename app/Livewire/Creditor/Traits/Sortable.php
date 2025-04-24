<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

trait Sortable
{
    public string $sortCol = '';

    public bool $sortAsc = true;

    public bool $withUrl = false;

    public function mountSortable(): void
    {
        $storedSorting = Session::get($this->getSessionKey());

        $this->sortCol = $storedSorting['sortCol'] ?? $this->sortCol;
        $this->sortAsc = $storedSorting['sortAsc'] ?? $this->sortAsc;
    }

    public function sortBy(string $column): void
    {
        if ($this->sortCol === $column) {
            $this->sortAsc = ! $this->sortAsc;

            $this->storeSorting();

            return;
        }

        $this->sortCol = $column;
        $this->sortAsc = false;

        $this->storeSorting();
    }

    protected function queryStringSortable(): array
    {
        if ($this->withUrl) {
            return [
                'sortCol' => ['as' => 'sort'],
                'sortAsc' => ['as' => 'direction'],
            ];
        }

        return [];
    }

    private function storeSorting(): void
    {
        Session::put($this->getSessionKey(), [
            'sortCol' => $this->sortCol,
            'sortAsc' => $this->sortAsc,
        ]);
    }

    private function getSessionKey(): string
    {
        $userId = Auth::id();
        $className = Str::replace('\\', '_', self::class);

        return "sortable_{$userId}_{$className}";
    }
}
