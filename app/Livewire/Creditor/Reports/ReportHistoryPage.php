<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Reports;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\ReportHistory;
use App\Services\ReportHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportHistoryPage extends Component
{
    use Sortable;
    use WithPagination;

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'created-on';
        $this->sortAsc = false;
    }

    public function downloadReport(ReportHistory $reportHistory): ?StreamedResponse
    {
        $fileName = 'download-report/' . Str::slug($reportHistory->report_type->value) . '/' . $reportHistory->downloaded_file_name;

        if (! Storage::exists($fileName)) {
            $this->error(__('There seems to be an error. Please email help@younegotiate.com so we can fix it.'));

            return null;
        }

        return Storage::download($fileName);
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'created-on' => 'created_at',
            'name' => 'report_type',
            'account-in-scope' => 'subclient_id',
            'records' => 'records',
            'start-date' => 'start_date',
            'end-date' => 'end_date',
            default => 'created_at'
        };

        $data = [
            'user_id' => Auth::id(),
            'per_page' => $this->perPage,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.reports.report-history-page')
            ->with('reportHistories', app(ReportHistoryService::class)->fetch($data))
            ->title(__('60-Day Report History'));
    }
}
