<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard\Stats;

use App\Enums\TransactionStatus;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class SuccessfulTransactionPage extends Component
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
            'date_time' => 'created_at',
            'amount' => 'amount',
            'member_account_number' => 'member_account_number',
            'consumer_name' => 'consumer_name',
            default => ''
        };

        $data = [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
            'per_page' => $this->perPage,
            'status' => TransactionStatus::SUCCESSFUL->value,
        ];

        return view('livewire.creditor.dashboard.stats.successful-transaction-page')
            ->with('transactions', app(TransactionService::class)->lastThirtyDays($data))
            ->title(__('Last 30 Days Successful Transactions'));
    }
}
