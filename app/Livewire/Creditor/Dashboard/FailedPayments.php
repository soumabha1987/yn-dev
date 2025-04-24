<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard;

use App\Exports\FailedPaymentsExport;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\ScheduleTransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportPagination\WithoutUrlPagination;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FailedPayments extends Component
{
    use Sortable;
    use WithoutUrlPagination;
    use WithPagination;

    public string $search = '';

    protected ScheduleTransactionService $scheduleTransactionService;

    private User $user;

    public function __construct()
    {
        $this->sortCol = 'due_date';
        $this->sortAsc = false;
        $this->scheduleTransactionService = app(ScheduleTransactionService::class);
        $this->user = Auth::user();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function export(): ?BinaryFileResponse
    {
        $failedTransactions = $this->scheduleTransactionService->exportFailedScheduleTransaction($this->setUp());

        if ($failedTransactions->isEmpty()) {
            $this->error(__('Sorry, there are no accounts to download. If you feel this is an error, please email help@younegotiate.com'));

            return null;
        }

        return Excel::download(
            new FailedPaymentsExport($failedTransactions),
            'failed_payments_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv',
            writerType: ExcelExcel::CSV,
        );
    }

    private function setUp(): array
    {
        $column = match ($this->sortCol) {
            'due_date' => 'schedule_date',
            'last_failed_date' => 'last_attempted_at',
            'account_number' => 'member_account_number',
            'consumer_name' => 'consumer_name',
            'account_name' => 'original_account_name',
            'sub_account_name' => 'subclient_name',
            'placement_date' => 'placement_date',
            default => 'schedule_date'
        };

        return [
            'company_id' => $this->user->company_id,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];
    }

    public function render(): View
    {
        $data = [
            ...$this->setUp(),
            'per_page' => $this->perPage,
        ];

        $title = __('Dashboard');
        $subtitle = __('(Rolling 30-day view)');

        return view('livewire.creditor.dashboard.failed-payments')
            ->with('scheduleTransactions', $this->scheduleTransactionService->fetchFailedScheduleTransaction($data))
            ->title(view('components.title', compact('title', 'subtitle')));
    }
}
