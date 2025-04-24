<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ConsumerPayTerms;

use App\Exports\ConsumersPayTermOfferExport;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\ConsumerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    protected ConsumerService $consumerService;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->sortCol = 'consumer-name';
        $this->consumerService = app(ConsumerService::class);
    }

    public function export(): StreamedResponse
    {
        $consumers = $this->consumerService->exportNotNullPayTerms($this->setUp());

        $downloadFilename = $this->user->id . '_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv';

        $filename = 'download-report/consumer-pay-terms/' . $downloadFilename;

        Excel::store(
            new ConsumersPayTermOfferExport($consumers),
            $filename,
            writerType: ExcelExcel::CSV
        );

        return Storage::download($filename);
    }

    private function setUp(): array
    {
        $column = match ($this->sortCol) {
            'member-account-number' => 'member_account_number',
            'consumer-name' => 'consumer_name',
            'sub-name' => 'subclient_name',
            'current-balance' => 'total_balance',
            'settlement-offer' => 'pif_discount_percent',
            'plan-balance-offer' => 'pay_setup_discount_percent',
            'min-monthly-payment' => 'amount',
            'max-days-first-pay' => 'max_days_first_pay',
            default => 'consumer_name',
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
        $title = __('Custom Consumer Offers');
        $subtitle = __('(created in "Consumer Profile")');

        $data = [
            ...$this->setUp(),
            'per_page' => $this->perPage,
        ];

        return view('livewire.creditor.consumer-pay-terms.list-page')
            ->with('consumers', $this->consumerService->fetchNotNullPayTerms($data))
            ->title(view('components.title', compact('title', 'subtitle')));
    }
}
