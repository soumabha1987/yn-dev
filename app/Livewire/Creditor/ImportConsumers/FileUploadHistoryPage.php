<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ImportConsumers;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\FileUploadHistory;
use App\Models\User;
use App\Services\FileUploadHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileUploadHistoryPage extends Component
{
    use Sortable;
    use WithPagination;

    public string $typeFilter = '';

    private User $user;

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'upload-date';
        $this->sortAsc = false;
        $this->user = Auth::user();
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function downloadFailedFile(FileUploadHistory $fileUploadHistory): ?StreamedResponse
    {
        if ($fileUploadHistory->failed_filename && Storage::exists('import_consumers/' . $fileUploadHistory->failed_filename)) {
            return Storage::download('import_consumers/' . $fileUploadHistory->failed_filename);
        }

        $this->error(__('All accounts uploaded successfully (no failed to report).'));

        return null;
    }

    public function downloadUploadedFile(FileUploadHistory $fileUploadHistory): ?StreamedResponse
    {
        if (Storage::exists('import_consumers/' . $fileUploadHistory->filename)) {
            return Storage::download('import_consumers/' . $fileUploadHistory->filename);
        }

        $this->error(__('So sorry, there seems to be a download error. Please email help@younegotiate.com to report the error.'));

        return null;
    }

    public function delete(FileUploadHistory $fileUploadHistory): void
    {
        $fileUploadHistory->update(['is_hidden' => true]);

        $this->dispatch('close-confirmation-box');

        $this->success(__('Deleted successfully.'));
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'upload-date' => 'created_at',
            'name' => 'filename',
            'type' => 'type',
            'records' => 'total_records',
            'successful-records' => 'processed_count',
            'failed-records' => 'failed_count',
            'sftp-import' => 'is_sftp_import',
            'status' => 'status',
            default => 'created_at'
        };

        $data = [
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
            'company_id' => $this->user->company_id,
            'per_page' => $this->perPage,
            'type_filter' => $this->typeFilter,
        ];

        return view('livewire.creditor.import-consumers.file-upload-history-page')
            ->with('fileUploadHistories', app(FileUploadHistoryService::class)->fetchByCompany($data))
            ->title(__('File Upload History'));
    }
}
