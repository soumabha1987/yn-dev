<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ImportConsumers;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerFields;
use App\Enums\FileUploadHistoryStatus;
use App\Enums\FileUploadHistoryType;
use App\Enums\MerchantName;
use App\Jobs\CalculateTotalRecordsJob;
use App\Jobs\ImportConsumersJob;
use App\Livewire\Creditor\Forms\ImportConsumers\IndexForm;
use App\Models\FileUploadHistory;
use App\Models\User;
use App\Services\CompanyMembershipService;
use App\Services\ConsumerService;
use App\Services\CsvHeaderService;
use App\Services\SetupWizardService;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class IndexPage extends Component
{
    use WithFileUploads;

    public IndexForm $form;

    public Collection $csvHeaders;

    public array $selectedHeader = [];

    public bool $companyIsNotVerified = false;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function mount(): void
    {
        $this->user->loadMissing('company.merchant');

        $company = $this->user->company;

        if (app(SetupWizardService::class)->getRemainingStepsCount($this->user) !== 0) {
            $this->redirectRoute('creditor.setup-wizard', navigate: true);
        }

        if (
            $company->merchant?->merchant_name === MerchantName::YOU_NEGOTIATE
            && $company->status !== CompanyStatus::ACTIVE
        ) {
            $this->companyIsNotVerified = true;
        }
    }

    public function updatedFormHeader($newValue): void
    {
        $this->selectedHeader = $this->csvHeaders->firstWhere('id', $newValue) ?? [];
    }

    public function importConsumers(): void
    {
        if (app(SetupWizardService::class)->getRemainingStepsCount($this->user) !== 0) {
            $this->error(__('Please complete all the steps before importing!'));

            return;
        }

        $validatedData = $this->form->validate();

        $stream = @fopen($validatedData['import_file']->path(), 'r');

        $header = @fgetcsv($stream);

        $header = array_filter($header, fn (string $value) => filled($value));

        if ($validatedData['import_type'] === FileUploadHistoryType::UPDATE->value && $this->checkFileHeaderForUpdateType()) {
            return;
        }

        if (array_diff($this->selectedHeader['headers'], $header)) {
            $this->error(__('Invalid CSV headers, ensure correct headers and remove extra headers if present in file.'));

            return;
        }

        if ($validatedData['import_type'] === FileUploadHistoryType::DELETE->value) {
            $this->selectedHeader['headers'] = [
                ConsumerFields::ACCOUNT_NUMBER->displayName() => $this->selectedHeader['headers'][ConsumerFields::ACCOUNT_NUMBER->displayName()],
            ];
        }

        $remainingConsumerCount = 0;

        if (in_array($validatedData['import_type'], [FileUploadHistoryType::ADD->value, FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB->value])) {
            $data = $this->validateCompanyMembershipUploadAccountLimit();

            if ($data['remaining_consumer_count'] === 0) {
                Notification::make('exceed_quota')
                    ->title(Str::markdown('**Quota Exceeded**'))
                    ->body(__('You have reached your maximum limit of :limit consumers.', ['limit' => $data['current_membership_upload_limit_count']]))
                    ->danger()
                    ->duration(10000)
                    ->icon('heroicon-o-exclamation-circle')
                    ->actions([
                        Action::make('upgrade-quota')
                            ->label(__('Upgrade plan'))
                            ->url(route('creditor.membership-settings')),
                        Action::make('cancel')
                            ->label('Close')
                            ->close(),
                    ])
                    ->send();

                return;
            }

            $remainingConsumerCount = (int) $data['remaining_consumer_count'];
        }

        $originalFileName = $validatedData['import_file']->getClientOriginalName();

        if (Storage::exists('import_consumers/' . $originalFileName)) {
            $i = 1;

            $fileName = pathinfo($originalFileName, PATHINFO_FILENAME);
            $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);

            while (Storage::exists('import_consumers/' . $fileName . "-$i." . $extension)) {
                $i++;
            }

            $originalFileName = $fileName . "-$i." . $extension;
        }

        $validatedData['import_file']->storeAs('import_consumers', $originalFileName, ['disk' => config('filesystems.default')]);

        $fileUploadHistory = FileUploadHistory::query()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => $originalFileName,
            'status' => FileUploadHistoryStatus::VALIDATING,
            'type' => $validatedData['import_type'],
            'total_records' => 0,
        ]);

        Bus::chain([
            new CalculateTotalRecordsJob($fileUploadHistory),
            new ImportConsumersJob(
                fileUploadHistory: $fileUploadHistory,
                csvHeader: $this->selectedHeader,
                remainingConsumerCount: $remainingConsumerCount,
            ),
        ])->dispatch();

        $this->redirectRoute('creditor.import-consumers.file-upload-history', navigate: true);
    }

    /**
     * @return array{
     *    current_membership_upload_limit_count: int,
     *    remaining_consumer_count: int
     * }
     */
    private function validateCompanyMembershipUploadAccountLimit(): array
    {
        $companyMembership = app(CompanyMembershipService::class)->findByCompany($this->user->company_id);

        $currentImportedConsumerCount = app(ConsumerService::class)->getCountByCompany($this->user->company_id);

        $remainingConsumerCount = max(0, $companyMembership->membership->upload_accounts_limit - $currentImportedConsumerCount);

        return [
            'current_membership_upload_limit_count' => $companyMembership->membership->upload_accounts_limit,
            'remaining_consumer_count' => $remainingConsumerCount,
        ];
    }

    private function checkFileHeaderForUpdateType(): bool
    {
        if (array_diff([ConsumerFields::ACCOUNT_NUMBER->displayName()], array_keys($this->selectedHeader['headers']))) {
            $this->error(__('The selected header file does not contain the updated field mappings.'));

            return true;
        }

        $this->selectedHeader['headers'] = array_intersect_key($this->selectedHeader['headers'], array_flip(ConsumerFields::getUpdateFields()));

        return false;
    }

    public function render(): View
    {
        $this->csvHeaders = app(CsvHeaderService::class)->fetchOnlyMapped($this->user->company_id, $this->user->subclient_id);

        return view('livewire.creditor.import-consumers.index-page')->title(__('Import Consumers'));
    }
}
