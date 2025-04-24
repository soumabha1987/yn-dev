<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard;

use App\Exports\DisputeAndNoPayingConsumerExport;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\ConsumerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportPagination\WithoutUrlPagination;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DisputeReports extends Component
{
    use Sortable;
    use WithoutUrlPagination;
    use WithPagination;

    public string $search = '';

    protected ConsumerService $consumerService;

    private User $user;

    public function __construct()
    {
        $this->sortCol = 'date';
        $this->sortAsc = false;
        $this->user = Auth::user();
        $this->consumerService = app(ConsumerService::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function export(): ?BinaryFileResponse
    {
        $consumers = $this->consumerService->getDeactivatedAndDispute($this->setupData());

        if ($consumers->isEmpty()) {
            $this->error(__('Sorry, there are no accounts to download. If you feel this is an error, please email help@younegotiate.com'));

            return null;
        }

        return Excel::download(
            new DisputeAndNoPayingConsumerExport($consumers),
            'disputes_no_pay_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv',
            writerType: ExcelExcel::CSV
        );
    }

    private function getColumn(): string
    {
        return match ($this->sortCol) {
            'date' => 'disputed_at',
            'account_balance' => 'current_balance',
            'consumer_name' => 'consumer_name',
            'account_number' => 'member_account_number',
            'account_name' => 'original_account_name',
            'sub_account_name' => 'subclient_name',
            'placement_date' => 'placement_date',
            default => 'disputed_at'
        };
    }

    private function setupData(): array
    {
        return [
            'search' => $this->search,
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'column' => $this->getColumn(),
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];
    }

    public function render(): View
    {
        $data = [
            ...$this->setupData(),
            'per_page' => $this->perPage,
        ];

        $title = __('Dashboard');
        $subtitle = __('(Rolling 30-day view)');

        return view('livewire.creditor.dashboard.dispute-reports')
            ->with('consumers', $this->consumerService->fetchDispute($data))
            ->title(view('components.title', compact('title', 'subtitle')));
    }
}
