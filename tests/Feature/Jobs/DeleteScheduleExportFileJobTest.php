<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\DeleteScheduleExportFileJob;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteScheduleExportFileJobTest extends TestCase
{
    #[Test]
    public function it_can_delete_the_file(): void
    {
        Storage::fake();

        Storage::put($filename = 'test.json', json_encode(['test' => 'test user'], JSON_PRETTY_PRINT));

        DeleteScheduleExportFileJob::dispatch($filename);

        Storage::assertMissing('test.json');
    }
}
