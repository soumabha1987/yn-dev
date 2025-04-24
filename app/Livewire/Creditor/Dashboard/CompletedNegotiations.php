<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard;

use App\Exports\CompletedNegotiationsExport;
use App\Livewire\Consumer\Traits\MyAccounts\StartOver;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Services\ConsumerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompletedNegotiations extends Component
{
    use Sortable;
    use StartOver;
    use WithoutUrlPagination;
    use WithPagination;

    public string $search = '';

    protected ConsumerService $consumerService;

    public function __construct()
    {
        $this->sortCol = 'promise-date';

        $this->consumerService = app(ConsumerService::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function export(): ?BinaryFileResponse
    {
        $negotiations = $this->consumerService->exportCompletedNegotiations($this->setUp());

        if ($negotiations->isEmpty()) {
            $this->error(__('Sorry, there are no accounts to download. If you feel this is an error, please email help@younegotiate.com'));

            return null;
        }

        return Excel::download(
            new CompletedNegotiationsExport($negotiations),
            'completed_negotiations_' . now()->format('Y_m_d_H_i_s') . '.csv',
            writerType: ExcelExcel::CSV
        );
    }

    private function getColumn(): string
    {
        return match ($this->sortCol) {
            'consumer-name' => 'consumer_name',
            'master-account-number' => 'member_account_number',
            'account-name' => 'original_account_name',
            'sub-account-name' => 'subclient_name',
            'placement-date' => 'placement_date',
            'offer-type' => 'negotiation_type',
            'beg-balance' => 'total_balance',
            'promise-amount' => 'monthly_amount',
            'pay-off-balance' => 'negotiate_amount',
            'promise-date' => 'first_pay_date',
            default => 'first_pay_date'
        };
    }

    private function setUp(): array
    {
        return [
            'search' => $this->search,
            'company_id' => Auth::user()->company_id,
            'column' => $this->getColumn(),
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

        return view('livewire.creditor.dashboard.completed-negotiations')
            ->with('consumers', $this->consumerService->fetchCompletedNegotiations($data))
            ->title(view('components.title', compact('title', 'subtitle')));
    }
}
