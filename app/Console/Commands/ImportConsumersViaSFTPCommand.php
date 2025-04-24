<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ConsumerFields;
use App\Enums\FileUploadHistoryStatus;
use App\Enums\FileUploadHistoryType;
use App\Jobs\GenerateErrorFileOfImportedConsumersViaSFTPJob;
use App\Jobs\ImportConsumersJob;
use App\Models\CsvHeader;
use App\Models\FileUploadHistory;
use App\Models\SftpConnection;
use App\Services\CompanyMembershipService;
use App\Services\ConsumerService;
use App\Services\SetupWizardService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportConsumersViaSFTPCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import-consumers:via-sftp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will automatically import consumers via SFTP into our system using headers.';

    protected CompanyMembershipService $companyMembershipService;

    protected ConsumerService $consumerService;

    /**
     * Execute the console command.
     */
    public function handle(
        CompanyMembershipService $companyMembershipService,
        ConsumerService $consumerService,
        SetupWizardService $setupWizardService
    ): int {
        $this->companyMembershipService = $companyMembershipService;
        $this->consumerService = $consumerService;

        $csvHeaders = CsvHeader::query()
            ->whereNotNull('sftp_connection_id')
            ->withWhereHas('sftpConnection', function (BelongsTo|Builder $query): void {
                $query->whereNotNull('import_filepath')->where('enabled', true);
            })
            ->withWhereHas('company.creditorUser')
            ->has('company.activeCompanyMembership')
            ->where('is_mapped', true)
            ->get();

        foreach ($csvHeaders as $csvHeader) {
            if ($setupWizardService->getRemainingStepsCount($csvHeader->company->creditorUser) !== 0) {
                continue;
            }

            /** @var SftpConnection $sftpConnection */
            $sftpConnection = $csvHeader->sftpConnection;

            try {
                $disk = Storage::createSftpDriver([
                    'host' => $sftpConnection->host,
                    'username' => $sftpConnection->username,
                    'password' => $sftpConnection->password,
                    'port' => filled($sftpConnection->port) ? ((int) $sftpConnection->port) : 22,
                    'timeout' => 360,
                ]);
            } catch (Exception $exception) {
                Log::channel('daily')->error('Sftp connection credentials are not working: ' . $exception->getMessage(), [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);

                continue;
            }

            $filepath = Str::of($sftpConnection->import_filepath)
                ->finish(DIRECTORY_SEPARATOR)
                ->toString();

            foreach (FileUploadHistoryType::getSftpImportFileNames($filepath, $csvHeader->name) as $fileUploadHistoryType => $filepath) {
                $files = collect($disk->allFiles($filepath))
                    ->filter(fn (string $file): bool => Str::endsWith($file, '.csv'));

                foreach ($files as $file) {
                    $stream = $disk->readStream($file);

                    $rowCount = 0;

                    while (($row = @fgetcsv($stream)) !== false) {
                        if (array_filter($row, fn ($value) => filled($value))) {
                            $rowCount++;
                        }
                    }

                    if ($rowCount === 0) {
                        continue;
                    }

                    $remainingConsumerCount = 0;

                    if (in_array($fileUploadHistoryType, [FileUploadHistoryType::ADD->value, FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB->value])) {
                        [$condition, $data] = $this->validateCompanyMembershipUploadAccountLimit($csvHeader->company_id, $rowCount);

                        if ($condition && $data['remaining_consumer_count'] === 0) {
                            continue;
                        }

                        $remainingConsumerCount = (int) $data['remaining_consumer_count'];
                    }

                    $originalFileName = pathinfo($file, PATHINFO_BASENAME);

                    if (Storage::exists('import_consumers/' . $originalFileName)) {
                        $i = 1;

                        $fileName = pathinfo($file, PATHINFO_FILENAME);
                        $extension = pathinfo($file, PATHINFO_EXTENSION);

                        while (Storage::exists('import_consumers/' . $fileName . "-$i." . $extension)) {
                            $i++;
                        }

                        $originalFileName = $fileName . "-$i." . $extension;
                    }

                    Storage::put("import_consumers/{$originalFileName}", $disk->get($file));

                    $fileUploadHistory = FileUploadHistory::query()->create([
                        'company_id' => $csvHeader->company_id,
                        'subclient_id' => $csvHeader->subclient_id,
                        'uploaded_by' => null,
                        'is_sftp_import' => true,
                        'filename' => $originalFileName,
                        'status' => FileUploadHistoryStatus::VALIDATING,
                        'type' => $fileUploadHistoryType,
                        'total_records' => $rowCount,
                    ]);

                    Bus::chain([
                        new ImportConsumersJob(
                            fileUploadHistory: $fileUploadHistory,
                            csvHeader: [
                                'id' => $csvHeader->id,
                                'name' => $csvHeader->name,
                                'date_format' => $csvHeader->date_format,
                                'headers' => collect($csvHeader->getAttribute('mapped_headers'))
                                    ->mapWithKeys(fn ($header, $key) => [ConsumerFields::tryFromValue($key)->displayName() => $header])
                                    ->toArray(),
                            ],
                            remainingConsumerCount: $remainingConsumerCount,
                        ),
                        new GenerateErrorFileOfImportedConsumersViaSFTPJob(
                            sftpConnection: $csvHeader->sftpConnection,
                            originalFilename: $originalFileName,
                            filename: $file,
                        ),
                    ])->dispatch();
                }
            }
        }

        return self::SUCCESS;
    }

    private function validateCompanyMembershipUploadAccountLimit(int $companyId, int $rowCount)
    {
        $companyMembership = $this->companyMembershipService->findByCompany($companyId);
        $currentImportedConsumerCount = $this->consumerService->getCountByCompany($companyId);

        $remainingConsumerCount = max(0, $companyMembership->membership->upload_accounts_limit - $currentImportedConsumerCount);

        return [
            $rowCount > $remainingConsumerCount,
            [
                'current_membership_upload_limit_count' => $companyMembership->membership->upload_accounts_limit,
                'existing_consumer_count' => $currentImportedConsumerCount,
                'remaining_consumer_count' => $remainingConsumerCount,
            ],
        ];
    }
}
