<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\PutScheduleExportOnSftpJob;
use App\Models\ScheduleExport;
use App\Models\SftpConnection;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PutScheduleExportOnSftpJobTest extends TestCase
{
    #[Test]
    public function it_will_put_into_sftp_details(): void
    {
        $this->travelTo(now()->addHours(2));

        $disk = Storage::fake();

        Storage::shouldReceive('createSftpDriver')->with([
            'host' => $host = '127.0.0.1',
            'username' => $username = fake()->userName(),
            'password' => $password = fake()->password(),
            'port' => 22,
            'timeout' => 360,
        ])->andReturn($disk);

        $scheduleExport = ScheduleExport::factory()->create([
            'sftp_connection_id' => SftpConnection::factory()->state([
                'host' => $host,
                'password' => $password,
                'username' => $username,
                'port' => 22,
                'enabled' => true,
                'export_filepath' => 'paste-reports-here/',
            ]),
            'emails' => null,
        ]);

        $disk->put($filename = 'paste-reports-here/test.json', json_encode(['test' => 'test user'], JSON_PRETTY_PRINT));

        Storage::shouldReceive('get')->with('test.json')->andReturn($disk->get($filename));

        PutScheduleExportOnSftpJob::dispatchSync($scheduleExport, 'test.json');

        $disk->assertExists('paste-reports-here/' . $scheduleExport->frequency->filename($scheduleExport->report_type->value));
    }
}
