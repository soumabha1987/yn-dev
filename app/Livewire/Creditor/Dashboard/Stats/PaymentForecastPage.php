<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard\Stats;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\ScheduleTransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class PaymentForecastPage extends Component
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
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'date_time' => 'schedule_date',
            'transaction_amount' => 'amount',
            'consumer_name' => 'consumer_name',
            'member_account_number' => 'member_account_number',
            default => ''
        };

        $data = [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'per_page' => $this->perPage,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.dashboard.stats.payment-forecast-page')
            ->with('scheduleTransactions', app(ScheduleTransactionService::class)->fetchPaymentForecast($data))
            ->title(__('30 days Forecast Scheduled Payment'));
    }
}
