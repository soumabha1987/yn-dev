<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SftpConnection;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateErrorFileOfImportedConsumersViaSFTPJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected SftpConnection $sftpConnection,
        protected string $originalFilename,
        protected string $filename,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $disk = Storage::createSftpDriver([
                'host' => $this->sftpConnection->host,
                'username' => $this->sftpConnection->username,
                'password' => $this->sftpConnection->password,
                'port' => filled($this->sftpConnection->port) ? ((int) $this->sftpConnection->port) : 22,
                'timeout' => 360,
            ]);

            $directory = pathinfo($this->filename, PATHINFO_DIRNAME);
            $filename = pathinfo($this->filename, PATHINFO_BASENAME);
            $disk->move($this->filename, $directory . DIRECTORY_SEPARATOR . 'proceed' . DIRECTORY_SEPARATOR . $filename);

            $errorFilename = pathinfo($this->originalFilename, PATHINFO_FILENAME) . '-failed.' . pathinfo($this->originalFilename, PATHINFO_EXTENSION);

            if (Storage::exists('import_consumers/' . $errorFilename)) {
                $disk->put($directory . DIRECTORY_SEPARATOR . 'failed' . DIRECTORY_SEPARATOR . $errorFilename, Storage::get('import_consumers/' . $errorFilename));
            }
        } catch (Exception $exception) {
            Log::channel('daily')->error('Failed to generate error file of imported consumers via SFTP.', [
                'message' => $exception->getMessage(),
                'sftpConnection' => $this->sftpConnection,
                'originalFilename' => $this->originalFilename,
                'filename' => $this->filename,
            ]);
        }
    }
}
