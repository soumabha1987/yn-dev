<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard\Stats;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Services\ScheduleTransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class FailedTransactionPage extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->sortCol = 'date_time';
        $this->sortAsc = false;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'date_time' => 'last_attempted_at',
            'amount' => 'amount',
            'consumer_name' => 'consumer_name',
            'member_account_number' => 'member_account_number',
            default => ''
        };

        $data = [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'search' => $this->search,
            'per_page' => $this->perPage,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.dashboard.stats.failed-transaction-page')
            ->with('scheduleTransactions', app(ScheduleTransactionService::class)->lastThirtyDays($data))
            ->title(__('Last 30 Days Failed Transactions'));
    }
}
