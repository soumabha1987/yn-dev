<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ImportConsumers;

use App\Livewire\Creditor\Forms\ImportConsumers\UploadFileForm;
use App\Models\CsvHeader;
use App\Models\User;
use App\Services\CsvHeaderService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadFilePage extends Component
{
    use WithFileUploads;

    public UploadFileForm $form;

    public string $selectedHeaderId = '';

    public ?CsvHeader $selectedHeader;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function updatedSelectedHeaderId(): void
    {
        $this->selectedHeader = null;

        if (filled($this->selectedHeaderId)) {
            $this->selectedHeader = app(CsvHeaderService::class)
                ->findById($this->selectedHeaderId, $this->user->company_id, $this->user->subclient_id);
        }
    }

    public function createHeader(): void
    {
        $validatedData = $this->form->validate();

        $stream = @fopen($validatedData['header_file']->path(), 'r');

        $uploadedFileHeaders = @fgetcsv($stream);

        @fclose($stream);

        $uploadedFileHeaders = array_filter($uploadedFileHeaders, fn (string $value) => filled($value));

        CsvHeader::query()
            ->select('headers', 'name')
            ->where('company_id', $this->user->company_id)
            ->when($this->user->subclient_id, function (Builder $query) {
                $query->where('subclient_id', $this->user->subclient_id);
            })
            ->get()
            ->each(function (CsvHeader $csvHeader) use ($uploadedFileHeaders) {
                $header = $csvHeader->getAttribute('import_Headers');

                if (count($header) === count($uploadedFileHeaders) && array_diff($header, $uploadedFileHeaders) === []) {
                    throw ValidationException::withMessages([
                        'form.header_file' => __('Header matches existing profile, [<b>:name</b>]', ['name' => $csvHeader->name]),
                    ]);
                }
            });

        $csvHeader = CsvHeader::query()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'name' => $validatedData['header_name'],
            'import_headers' => $uploadedFileHeaders,
        ]);

        $this->success(__('CSV file uploaded successfully!'));

        $this->redirectRoute('creditor.import-consumers.upload-file.map', ['csvHeader' => $csvHeader], navigate: true);
    }

    public function deleteSelectedHeader(CsvHeader $csvHeader): void
    {
        $csvHeader->delete();

        $this->success(__('Header profile deleted.'));

        Cache::forget('remaining-wizard-required-steps-' . $this->user->id);

        $this->dispatch('close-confirmation-box');

        $this->redirectRoute('creditor.import-consumers.upload-file', navigate: true);
    }

    public function downloadUploadedFile(CsvHeader $csvHeader): ?StreamedResponse
    {
        $headers = data_get($csvHeader->headers, 'import_headers', false);

        if (! $headers) {
            $this->error(__('Header is showing blank. If this is an error, please contact help@younegotiate.com.'));

            return null;
        }

        $cleanedHeaders = array_map(fn ($header) => trim(str_replace(['\n', '\r'], ' ', $header)), $headers);

        return response()->streamDownload(function () use ($cleanedHeaders) {
            echo implode(',', $cleanedHeaders);
        }, 'header.csv');
    }

    public function resetHeaderFileValidation(): void
    {
        $this->resetValidation('form.header_file');
    }

    public function render(): View
    {
        $title = __('Create Import Header Profile(s)');
        $subtitle = __('Upload and Map any CSV Layout');

        return view('livewire.creditor.import-consumers.upload-file-page')
            ->with('csvHeaders', app(CsvHeaderService::class)->fetchByCompanyId($this->user->company_id, $this->user->subclient_id))
            ->title(view('components.title', compact('title', 'subtitle')));
    }
}
