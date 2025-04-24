<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerFields;
use App\Enums\ConsumerStatus;
use App\Enums\FileUploadHistoryStatus;
use App\Enums\FileUploadHistoryType;
use App\Enums\TransactionStatus;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\FileUploadHistory;
use App\Models\ScheduleTransaction;
use App\Models\Subclient;
use App\Services\ConsumerService;
use App\Services\ConsumerUnsubscribeService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator as ValidationValidator;
use Throwable;

class ImportConsumersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $chunkSize = 500;

    public string $dateFormat;

    protected ConsumerService $consumerService;

    protected ConsumerUnsubscribeService $consumerUnsubscribeService;

    /**
     * @param array{
     *    id: int,
     *    name: string,
     *    date_format: string,
     *    headers: array<string, string>
     * } $csvHeader
     */
    public function __construct(
        public FileUploadHistory $fileUploadHistory,
        public array $csvHeader,
        public int $remainingConsumerCount
    ) {
        $this->consumerService = app(ConsumerService::class);
        $this->consumerUnsubscribeService = app(ConsumerUnsubscribeService::class);
    }

    public function handle(): void
    {
        if ($this->fileUploadHistory->status === FileUploadHistoryStatus::FAILED) {
            return;
        }

        $this->dateFormat = $this->csvHeader['date_format'];

        Log::channel('import_consumers')->info('Import Consumers Starting', [
            'file_upload_history' => $this->fileUploadHistory->id,
            'selected_date_format' => $this->dateFormat,
        ]);

        if (! Storage::exists('import_consumers/' . $this->fileUploadHistory->filename)) {
            Log::channel('import_consumers')->error('File not found when import consumers', [
                'filename' => $this->fileUploadHistory->filename,
                'file_upload_history' => $this->fileUploadHistory->id,
            ]);

            return;
        }

        $this->fetchCsvDataFromFile();

        $this->fileUploadHistory->update(['status' => FileUploadHistoryStatus::COMPLETE]);

        $this->fileUploadHistory->refresh();

        Log::channel('import_consumers')->info('Import Consumers Finished', [
            'file_upload_history' => $this->fileUploadHistory->id,
            'transaction' => $this->fileUploadHistory->type->displayMessage(),
            'processed_count' => $this->fileUploadHistory->processed_count,
            'failed_count' => $this->fileUploadHistory->failed_count,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('import_consumers')->error('Sending an email or sms failed', [
            'message' => $exception->getMessage(),
            'stack trace' => $exception->getTrace(),
            'file_upload_history_id' => $this->fileUploadHistory->id,
        ]);

        $this->fileUploadHistory->update(['status' => FileUploadHistoryStatus::FAILED]);
    }

    private function createConsumers(array $consumerRows): void
    {
        foreach ($consumerRows as $consumerRow) {
            $consumerRow['dob'] = Carbon::parse($consumerRow['dob'])->format($this->dateFormat);

            $consumerRow['last4ssn'] = substr($consumerRow['last4ssn'], -4);
            $data = Arr::only($consumerRow, ['last_name', 'dob', 'last4ssn']);
            $data['dob'] = Carbon::createFromFormat($this->dateFormat, $data['dob'])->toDateString();

            $consumerHaveSameLastNameDobAndSsn = Consumer::query()
                ->select('consumer_profile_id')
                ->where($data)
                ->whereNotNull('consumer_profile_id')
                ->first();

            $consumerProfile = null;

            if (blank($consumerHaveSameLastNameDobAndSsn)) {
                $consumerProfile = ConsumerProfile::query()->create([
                    'first_name' => $consumerRow['first_name'],
                    'address' => $consumerRow['address1'] ?? null,
                    'city' => $consumerRow['city'] ?? null,
                    'state' => $consumerRow['state'] ?? null,
                    'zip' => $consumerRow['zip'] ?? null,
                    'mobile' => $consumerRow['mobile1'],
                    'landline' => $consumerRow['landline1'] ?? null,
                    'email' => $consumerRow['email1'],
                    'username' => $consumerRow['first_name'] . '-' . $consumerRow['last_name'],
                    'email_permission' => true,
                    'text_permission' => true,
                ]);
            }

            $consumer = $this->createConsumer([
                ...$consumerRow,
                'dob' => $data['dob'],
                'subclient_id' => Subclient::query()
                    ->where('company_id', $this->fileUploadHistory->company_id)
                    ->where('unique_identification_number', data_get($consumerRow, 'subclient_id'))
                    ->value('id'),
                'total_balance' => $consumerRow['current_balance'],
                'consumer_profile_id' => $consumerHaveSameLastNameDobAndSsn
                    ? $consumerHaveSameLastNameDobAndSsn->consumer_profile_id
                    : $consumerProfile->id,
            ]);

            $this->sendAnEmail(
                $consumerHaveSameLastNameDobAndSsn ? CommunicationCode::NEW_ACCOUNT : CommunicationCode::WELCOME,
                $consumer
            );

            $this->fileUploadHistory->increment('processed_count');
        }
    }

    private function updateConsumers(array $consumersToUpdate, array $consumersProfileToUpdate): void
    {
        $columnNames = array_keys($consumersToUpdate[0]);
        Arr::forget($columnNames, ['id']);

        Consumer::query()->upsert(
            $consumersToUpdate,
            ['id'],
            $columnNames,
        );

        if (filled($consumersProfileToUpdate)) {
            ConsumerProfile::query()->upsert(
                $consumersProfileToUpdate,
                ['id'],
                ['email', 'mobile', 'email_permission', 'text_permission'],
            );
        }

        $this->fileUploadHistory->increment('processed_count', count($consumersToUpdate));
    }

    private function deactivateConsumers(array $consumerIdsToDeactivate): void
    {
        Consumer::query()
            ->whereIn('id', $consumerIdsToDeactivate)
            ->update([
                'status' => ConsumerStatus::DEACTIVATED,
                'disputed_at' => now(),
                'reason_id' => null,
                'restart_date' => null,
                'hold_reason' => null,
            ]);

        ScheduleTransaction::query()
            ->whereIn('status', [TransactionStatus::FAILED, TransactionStatus::SCHEDULED])
            ->whereIn('consumer_id', $consumerIdsToDeactivate)
            ->update(['status' => TransactionStatus::CANCELLED]);

        $this->fileUploadHistory->increment('processed_count', count($consumerIdsToDeactivate));

        ImportDeactivatedConsumersJob::dispatch($consumerIdsToDeactivate);
    }

    private function fetchCsvDataFromFile(): void
    {
        $uploadedFilePath = Storage::path('import_consumers/' . $this->fileUploadHistory->filename);

        $stream = @fopen($uploadedFilePath, 'r');

        $uploadedFileHeaders = @fgetcsv($stream);

        $uploadedFileHeaders = array_values(array_filter($uploadedFileHeaders, fn (string $value) => filled($value)));

        $mappedHeaders = [];

        foreach ($this->csvHeader['headers'] as $consumerFieldDisplayName => $header) {
            $index = array_search($header, $uploadedFileHeaders);
            if ($index !== false) {
                $mappedHeaders[$consumerFieldDisplayName] = $index;
            }
        }

        $consumersToCreate = [];
        $consumersToUpdate = [];
        $consumersProfileToUpdate = [];
        $consumersIdToDeactivate = [];

        $validationErrors = [];

        $consumerRowIndex = 0;

        $isPpaBalanceDiscountPercentage = false;
        $isMinMonthlyPercentage = false;

        $validator = null;

        while (($row = @fgetcsv($stream)) !== false) {
            $row = array_map('trim', $row);

            if (blank(array_filter($row))) {
                continue;
            }

            $consumerData = [];
            $consumerUpdateData = [];
            $foundConsumer = null;

            $accountNumber = $row[$mappedHeaders[ConsumerFields::ACCOUNT_NUMBER->displayName()]];

            if (filled($consumersToCreate) && in_array($accountNumber, array_column($consumersToCreate, ConsumerFields::ACCOUNT_NUMBER->value))) {
                $validationErrors[$consumerRowIndex] = 'Duplicate account number detected in the file.';
            }

            if (in_array($this->fileUploadHistory->type, [FileUploadHistoryType::UPDATE, FileUploadHistoryType::DELETE])) {
                $foundConsumer = Consumer::query()
                    ->where('company_id', $this->fileUploadHistory->company_id)
                    ->where('account_number', $accountNumber)
                    ->first();

                if (blank($foundConsumer)) {
                    $failedData = array_combine(
                        array_map(fn ($field) => ConsumerFields::fromDisplayName($field)->value, array_keys($mappedHeaders)),
                        array_map(fn ($header) => $row[$header], $mappedHeaders)
                    );

                    $this->generateFailedRecordsFile([
                        'existing_data' => $failedData,
                        'validation_errors' => ['errors' => "We couldn't locate this account number in our records."],
                        'uploaded_file_headers' => $this->csvHeader['headers'],
                    ]);

                    continue;
                }

                if ($this->fileUploadHistory->type === FileUploadHistoryType::DELETE) {

                    if ($foundConsumer->status === ConsumerStatus::DEACTIVATED) {
                        $failedData = array_combine(
                            array_map(fn ($field) => ConsumerFields::fromDisplayName($field)->value, array_keys($mappedHeaders)),
                            array_map(fn ($header) => $row[$header], $mappedHeaders)
                        );

                        $this->generateFailedRecordsFile([
                            'existing_data' => $failedData,
                            'validation_errors' => ['errors' => 'This consumer account is already deactivated.'],
                            'uploaded_file_headers' => $this->csvHeader['headers'],
                        ]);

                        continue;
                    }

                    $consumersIdToDeactivate[] = $foundConsumer->id;

                    if (count($consumersIdToDeactivate) % $this->chunkSize === 0) {
                        $this->deactivateConsumers($consumersIdToDeactivate);
                        $consumersIdToDeactivate = [];
                    }

                    continue;
                }

                $isPpaBalanceDiscountPercentage = array_key_exists(ConsumerFields::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE->displayName(), $mappedHeaders)
                    && filled($row[$mappedHeaders[ConsumerFields::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE->displayName()]]);

                $isMinMonthlyPercentage = array_key_exists(ConsumerFields::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE->displayName(), $mappedHeaders)
                    && filled($row[$mappedHeaders[ConsumerFields::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE->displayName()]]);
            }

            foreach ($mappedHeaders as $consumerFieldDisplayName => $mappedHeader) {
                $consumerField = ConsumerFields::fromDisplayName($consumerFieldDisplayName);

                if ($consumerField === ConsumerFields::PHONE) {
                    $row[$mappedHeader] = preg_replace('/\D+/', '', $row[$mappedHeader]);
                }

                if (in_array($this->fileUploadHistory->type, [FileUploadHistoryType::ADD, FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB])) {
                    if ($this->remainingConsumerCount === 0) {
                        $validationErrors[$consumerRowIndex] = __('Sorry, you have reached the account limit, so this consumer cannot be uploaded.');
                        $consumerData[$consumerField->value] = $row[$mappedHeader];

                        continue;
                    }

                    $validator = $this->validateConsumerData($consumerField, $row[$mappedHeader]);

                    $consumerData[$consumerField->value] = filled($row[$mappedHeader]) ? $row[$mappedHeader] : null;
                }

                if ($this->fileUploadHistory->type === FileUploadHistoryType::UPDATE) {
                    if ($consumerField === ConsumerFields::ACCOUNT_NUMBER) {
                        $consumerData[$consumerField->value] = $row[$mappedHeader];

                        continue;
                    }

                    $validator = $this->updateFieldsValidation($consumerField, $row[$mappedHeader], $isPpaBalanceDiscountPercentage, $isMinMonthlyPercentage);

                    $consumerData[$consumerField->value] = $row[$mappedHeader];

                    $consumerUpdateData[$consumerField->value] = filled($row[$mappedHeader]) ? $row[$mappedHeader] : $foundConsumer->{$consumerField->value};
                }

                if ($validator->fails()) {
                    $validationErrors[$consumerRowIndex] ?? false
                        ? $validationErrors[$consumerRowIndex] .= ', ' . implode(', ', $validator->errors()->get($consumerField->value))
                        : $validationErrors[$consumerRowIndex] = implode(', ', $validator->errors()->get($consumerField->value));
                }
            }

            if ($validationErrors[$consumerRowIndex] ?? false) {
                $this->generateFailedRecordsFile([
                    'existing_data' => $consumerData,
                    'validation_errors' => ['errors' => $validationErrors[$consumerRowIndex]],
                    'uploaded_file_headers' => $this->csvHeader['headers'],
                ]);

                $consumerRowIndex++;

                continue;
            }

            $consumerRowIndex++;

            if ($this->fileUploadHistory->type === FileUploadHistoryType::UPDATE) {
                $consumerUpdateData['id'] = $foundConsumer->id;

                if (
                    ($foundConsumer->mobile1 === $foundConsumer->consumerProfile->mobile || $foundConsumer->email1 === $foundConsumer->consumerProfile->email)
                    && (filled($consumerUpdateData[ConsumerFields::CONSUMER_EMAIL->value]) || filled($consumerUpdateData[ConsumerFields::PHONE->value]))
                ) {
                    $consumerProfileData['email'] =
                        filled($consumerUpdateData[ConsumerFields::CONSUMER_EMAIL->value]) && $foundConsumer->email1 === $foundConsumer->consumerProfile->email
                        ? $consumerUpdateData[ConsumerFields::CONSUMER_EMAIL->value] : $foundConsumer->consumerProfile->email;

                    $consumerProfileData['email_permission'] =
                        filled($consumerUpdateData[ConsumerFields::CONSUMER_EMAIL->value]) && $foundConsumer->email1 === $foundConsumer->consumerProfile->email
                        ? true : $foundConsumer->consumerProfile->email_permission;

                    $consumerProfileData['mobile'] =
                        filled($consumerUpdateData[ConsumerFields::PHONE->value]) && $foundConsumer->mobile1 === $foundConsumer->consumerProfile->mobile
                        ? $consumerUpdateData[ConsumerFields::PHONE->value] : $foundConsumer->consumerProfile->mobile;

                    $consumerProfileData['text_permission'] =
                        filled($consumerUpdateData[ConsumerFields::PHONE->value]) && $foundConsumer->mobile1 === $foundConsumer->consumerProfile->mobile
                        ? true : $foundConsumer->consumerProfile->text_permission;

                    $consumerProfileData['id'] = $foundConsumer->consumer_profile_id;

                    $consumersProfileToUpdate[$foundConsumer->consumer_profile_id] = $consumerProfileData;
                }

                $consumersToUpdate[] = $consumerUpdateData;

                if (count($consumersToUpdate) % $this->chunkSize === 0) {
                    $this->updateConsumers($consumersToUpdate, $consumersProfileToUpdate);
                    $consumersToUpdate = [];
                    $consumersProfileToUpdate = [];
                }

                continue;
            }

            $this->remainingConsumerCount--;

            $consumersToCreate[] = $consumerData;

            if (count($consumersToCreate) % $this->chunkSize === 0) {
                $this->createConsumers($consumersToCreate);
                $consumersToCreate = [];
            }
        }

        if (filled($consumersToCreate)) {
            $this->createConsumers($consumersToCreate);
        }

        if (filled($consumersToUpdate)) {
            $this->updateConsumers($consumersToUpdate, $consumersProfileToUpdate);
        }

        if (filled($consumersIdToDeactivate)) {
            $this->deactivateConsumers($consumersIdToDeactivate);
        }
    }

    private function updateFieldsValidation(ConsumerFields $consumerField, string $csvData, bool $isPpaBalanceDiscountPercentage, bool $isMinMonthlyPercentage): ValidationValidator
    {
        return Validator::make(
            [$consumerField->value => $csvData],
            [$consumerField->value => $consumerField->updateValidate($isPpaBalanceDiscountPercentage, $isMinMonthlyPercentage)],
        );
    }

    private function validateConsumerData(ConsumerFields $consumerField, string $csvData): ValidationValidator
    {
        if (in_array($consumerField->value, ConsumerFields::getDecimalFields())) {
            $csvDataWithoutComma = str_replace(',', '', $csvData);
            if (is_numeric($csvDataWithoutComma)) {
                $csvData = number_format((float) $csvDataWithoutComma, 2, '.', '');
            }
        }

        return Validator::make(
            [$consumerField->value => $csvData],
            [$consumerField->value => $consumerField->validate($this->fileUploadHistory->company_id)],
            $consumerField->customMessage(),
        );
    }

    private function generateFailedRecordsFile(array $data): void
    {
        $originalFileName = $this->fileUploadHistory->filename;

        $fileName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);

        $errorFileName = $fileName . '-failed.' . $extension;

        $stream = @fopen(Storage::path('import_consumers/' . $errorFileName), 'a+');

        if (@fgetcsv($stream) === false) {
            $this->fileUploadHistory->update(['failed_filename' => $errorFileName]);
            @fputcsv($stream, [...$data['uploaded_file_headers'], 'Errors']);
        }

        @fputcsv($stream, array_values($data['existing_data'] + $data['validation_errors']));

        @fclose($stream);

        $this->fileUploadHistory->increment('failed_count');
    }

    private function sendAnEmail(CommunicationCode $communicationCode, Consumer $consumer): void
    {
        try {
            $consumer->loadMissing(['consumerProfile', 'subclient', 'company']);

            TriggerEmailAndSmsServiceJob::dispatch($consumer, $communicationCode);
        } catch (Exception $exception) {
            Log::channel('import_consumers')->error('While sending an email to consumer', [
                'consumer_id' => $consumer->id,
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function createConsumer(array $row): Consumer
    {
        $invitationLink = Str::of(config('services.yng.short_link'))
            ->finish('/')
            ->append(Str::random(3))
            ->append(Str::substr(now()->timestamp, -4))
            ->append(Str::random(3))
            ->toString();

        return Consumer::query()->updateOrCreate(
            [
                'company_id' => $this->fileUploadHistory->company_id,
                'account_number' => $row['account_number'],
            ],
            [
                ...$row,
                'status' => ConsumerStatus::UPLOADED->value,
                'company_id' => $this->fileUploadHistory->company_id,
                'invitation_link' => $invitationLink,
                'file_upload_history_id' => $this->fileUploadHistory->id,
                'reason_id' => null,
            ]
        );
    }
}
