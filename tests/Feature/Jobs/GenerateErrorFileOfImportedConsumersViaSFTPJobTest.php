<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\GenerateErrorFileOfImportedConsumersViaSFTPJob;
use App\Models\SftpConnection;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateErrorFileOfImportedConsumersViaSFTPJobTest extends TestCase
{
    #[Test]
    public function it_can_generate_error_file_and_move_proceed_file(): void
    {
        $sftpConnection = SftpConnection::factory()->create();

        $disk = Storage::fake();
        Storage::shouldReceive('createSftpDriver')->withAnyArgs()->andReturn($disk);
        Storage::shouldReceive('put')->withAnyArgs();
        Storage::shouldReceive('exists')->withAnyArgs()->andReturnTrue();
        Storage::shouldReceive('get')->withAnyArgs()->andReturn(json_encode([]));

        $disk->put('YouNegotiate/import/Header Profile/Add New Accounts/xyz.csv', json_encode([]));
        $disk->put('import_consumers/xyz-failed.csv', json_encode([]));

        GenerateErrorFileOfImportedConsumersViaSFTPJob::dispatchSync(
            $sftpConnection,
            'xyz.csv',
            'YouNegotiate/import/Header Profile/Add New Accounts/xyz.csv'
        );

        $disk->assertMissing('YouNegotiate/import/Header Profile/Add New Accounts/xyz.csv');
        $disk->assertExists('YouNegotiate/import/Header Profile/Add New Accounts/proceed/xyz.csv');
        $disk->assertExists('YouNegotiate/import/Header Profile/Add New Accounts/failed/xyz-failed.csv');
    }
}
