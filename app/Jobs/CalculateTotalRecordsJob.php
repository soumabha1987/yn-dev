<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\FileUploadHistoryStatus;
use App\Models\FileUploadHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CalculateTotalRecordsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected FileUploadHistory $fileUploadHistory,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $stream = Storage::readStream('import_consumers/' . $this->fileUploadHistory->filename);

        if (! $stream) {
            $this->fileUploadHistory->update([
                'status' => FileUploadHistoryStatus::FAILED,
                'total_records' => 0,
            ]);

            return;
        }

        try {
            $rowCount = 0;

            @fgetcsv($stream);

            while (($row = @fgetcsv($stream)) !== false) {
                if (array_filter($row, fn ($value) => filled($value))) {
                    $rowCount++;
                }
            }

            $this->fileUploadHistory->update([
                'total_records' => $rowCount,
                'status' => $rowCount > 0 ? $this->fileUploadHistory->status : FileUploadHistoryStatus::FAILED,
            ]);

            if ($rowCount === 0) {
                Storage::delete('import_consumers/' . $this->fileUploadHistory->filename);
            }
        } finally {
            @fclose($stream);
        }
    }
}
