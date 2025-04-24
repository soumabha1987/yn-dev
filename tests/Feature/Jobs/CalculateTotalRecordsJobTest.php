<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\FileUploadHistoryStatus;
use App\Jobs\CalculateTotalRecordsJob;
use App\Models\FileUploadHistory;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CalculateTotalRecordsJobTest extends TestCase
{
    #[Test]
    public function it_can_calculate_the_count_of_total_records_in_uploaded_file(): void
    {
        Storage::fake();

        $fileUploadHistory = FileUploadHistory::factory()
            ->create([
                'filename' => 'test.csv',
                'status' => FileUploadHistoryStatus::VALIDATING,
                'total_records' => 0,
            ]);

        Storage::put('import_consumers/' . $fileUploadHistory->filename, "name,email\nJohn Doe,john@example.com\nJane Doe,jane@example.com");

        CalculateTotalRecordsJob::dispatchSync($fileUploadHistory);

        $fileUploadHistory->refresh();

        $this->assertEquals(2, $fileUploadHistory->refresh()->total_records);
        $this->assertEquals(FileUploadHistoryStatus::VALIDATING, $fileUploadHistory->status);
    }

    #[Test]
    public function if_file_has_no_records_then_it_will_update_to_failed_and_delete_the_file(): void
    {
        Storage::fake();

        $fileUploadHistory = FileUploadHistory::factory()->create([
            'filename' => 'empty.csv',
            'total_records' => 0,
        ]);

        Storage::put('import_consumers/' . $fileUploadHistory->filename, "name,email\n");

        CalculateTotalRecordsJob::dispatchSync($fileUploadHistory);

        $fileUploadHistory->refresh();

        $this->assertEquals(0, $fileUploadHistory->refresh()->total_records);
        $this->assertEquals(FileUploadHistoryStatus::FAILED, $fileUploadHistory->status);
        $this->assertFalse(Storage::exists('import_consumers/empty.csv'));
    }
}
