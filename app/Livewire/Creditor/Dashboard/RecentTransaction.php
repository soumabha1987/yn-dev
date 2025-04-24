<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard;

use App\Exports\RecentTransactionsExport;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RecentTransaction extends Component
{
    use Sortable;
    use WithoutUrlPagination;
    use WithPagination;

    public string $search = '';

    protected TransactionService $transactionService;

    private User $user;

    public function __construct()
    {
        $this->sortCol = 'date';
        $this->sortAsc = false;
        $this->transactionService = app(TransactionService::class);
        $this->user = Auth::user();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function export(): ?BinaryFileResponse
    {
        $transactions = $this->transactionService->getRecentlyOfCompany($this->setUpData());

        if ($transactions->isEmpty()) {
            $this->error(__('Sorry, there are no accounts to download. If you feel this is an error, please email help@younegotiate.com'));

            return null;
        }

        return Excel::download(
            new RecentTransactionsExport($transactions),
            'recent_payments_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv',
            writerType: ExcelExcel::CSV
        );
    }

    private function setUpData(): array
    {
        $column = match ($this->sortCol) {
            'date' => 'created_at',
            'consumer_name' => 'consumer_name',
            'account_number' => 'member_account_number',
            'transaction_type' => 'transaction_type',
            'amount' => 'amount',
            'subclient_name' => 'subclient_name',
            'placement_date' => 'placement_date',
            default => 'created_at'
        };

        return [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];
    }

    public function render(): View
    {
        $data = [
            ...$this->setUpData(),
            'per_page' => $this->perPage,
        ];

        $title = __('Dashboard');
        $subtitle = __('(Rolling 30-day view)');

        return view('livewire.creditor.dashboard.recent-transaction')
            ->with('transactions', $this->transactionService->fetchRecentlyOfCompany($data))
            ->title(view('components.title', compact('title', 'subtitle')));
    }
}
