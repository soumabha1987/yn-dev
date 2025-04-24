<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ImportConsumers;

use App\Enums\ConsumerFields;
use App\Enums\FileUploadHistoryDateFormat;
use App\Models\CsvHeader;
use App\Models\User;
use App\Services\SetupWizardService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MapUploadedFilePage extends Component
{
    public User $user;

    public CsvHeader $csvHeader;

    public string $date_format = '';

    public array $requiredFields = [];

    public array $mappedHeaders = [];

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function mount(): void
    {
        $this->requiredFields = ConsumerFields::getRequiredFields();

        $this->mappedHeaders = $this->csvHeader->getAttribute('mapped_headers');
        $this->date_format = $this->csvHeader->date_format ?? '';
    }

    public function deleteHeader(): void
    {
        $mappedHeader = $this->csvHeader->getAttribute('mapped_headers');

        if ($mappedHeader) {
            $this->redirectRoute('creditor.import-consumers.upload-file', navigate: true);

            return;
        }

        $this->csvHeader->delete();

        $this->success(__('The header file was not saved and was successfully deleted.'));

        Cache::forget('remaining-wizard-required-steps-' . $this->user->id);

        $this->redirectRoute('creditor.import-consumers.upload-file', navigate: true);
    }

    public function finishLater(): void
    {
        $validatedData = $this->validate(
            [
                'date_format' => ['required', 'string', Rule::in(FileUploadHistoryDateFormat::values())],
                'mappedHeaders' => ['required', 'array'],
            ],
            ['mappedHeaders' => __('Map a minimum of one mapped data field to finish later')]
        );

        if (app(SetupWizardService::class)->isLastRequiredStepRemaining($this->user)) {
            Session::put('show-wizard-completed-modal', true);

            Cache::forget('remaining-wizard-required-steps-' . $this->user->id);
        }

        $this->csvHeader->update([
            'date_format' => $validatedData['date_format'],
            'mapped_headers' => array_filter($validatedData['mappedHeaders']),
            'is_mapped' => false,
        ]);

        $this->success(__('Header Profile saved.'));

        $this->redirectRoute('creditor.import-consumers.upload-file', navigate: true);
    }

    public function storeMappedHeaders(): void
    {
        $requiredFields = ConsumerFields::getRequiredFields();
        $requiredHeadersValidation = collect(ConsumerFields::values())
            ->mapWithKeys(fn ($item) => ['mappedHeaders.' . $item => in_array($item, $requiredFields) ? 'required' : 'sometimes'])
            ->all();
        $validatedData = $this->validate([
            'date_format' => ['required', 'string', Rule::in(FileUploadHistoryDateFormat::values())],
            'mappedHeaders' => ['required', 'array'],
        ] + $requiredHeadersValidation);

        $setupWizardService = app(SetupWizardService::class);

        $isNotCompletedSetupWizard = $setupWizardService->cachingRemainingRequireStepCount($this->user) !== 0;

        if ($isNotCompletedSetupWizard && $setupWizardService->isLastRequiredStepRemaining($this->user)) {
            Session::put('show-wizard-completed-modal', true);

            Cache::forget('remaining-wizard-required-steps-' . $this->user->id);
        }

        $this->csvHeader->update([
            'date_format' => $validatedData['date_format'],
            'mapped_headers' => array_filter($validatedData['mappedHeaders']),
            'is_mapped' => true,
        ]);

        $this->success(__('Header Profile saved!'));

        if ($isNotCompletedSetupWizard) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->redirectRoute('creditor.import-consumers.upload-file', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.import-consumers.map-uploaded-file-page')
            ->title(__('Map header file'));
    }
}
