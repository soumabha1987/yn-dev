<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard;

use App\Exports\UpcomingTransactionsExport;
use App\Livewire\Creditor\ManageConsumers\ConsumerProfile\Reschedule;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\ScheduleTransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UpcomingTransaction extends Component
{
    use Reschedule;
    use Sortable;
    use WithoutUrlPagination;
    use WithPagination;

    public string $search = '';

    protected ScheduleTransactionService $scheduleTransactionService;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->sortCol = 'schedule_date';
        $this->sortAsc = false;
        $this->scheduleTransactionService = app(ScheduleTransactionService::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function export(): ?BinaryFileResponse
    {
        $transactions = $this->scheduleTransactionService->getUpcomingTransactions($this->setUpData());

        if ($transactions->isEmpty()) {
            $this->error(__('Sorry, there are no accounts to download. If you feel this is an error, please email help@younegotiate.com'));

            return null;
        }

        return Excel::download(
            new UpcomingTransactionsExport($transactions),
            'upcoming_payments_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv',
            writerType: ExcelExcel::CSV
        );
    }

    private function getColumn(): string
    {
        return match ($this->sortCol) {
            'schedule_date' => 'schedule_date',
            'amount' => 'amount',
            'account_number' => 'member_account_number',
            'consumer_name' => 'consumer_name',
            'account_name' => 'original_account_name',
            'sub_name' => 'subclient_name',
            'placement_date' => 'placement_date',
            'pay_type' => 'transaction_type',
            default => 'schedule_date'
        };
    }

    public function setUpData(): array
    {
        return [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'search' => $this->search,
            'column' => $this->getColumn(),
            'direction' => $this->sortAsc ? 'DESC' : 'ASC',
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

        return view('livewire.creditor.dashboard.upcoming-transaction')
            ->with('transactions', app(ScheduleTransactionService::class)->fetchUpcomingTransactions($data))
            ->title(view('components.title', compact('title', 'subtitle')));
    }
}
