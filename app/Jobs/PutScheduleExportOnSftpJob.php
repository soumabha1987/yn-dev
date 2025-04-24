<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ScheduleExport;
use App\Services\SftpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PutScheduleExportOnSftpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ScheduleExport $scheduleExport,
        protected string $filename,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SftpService $sftpService): void
    {
        $this->scheduleExport->loadMissing('sftpConnection');

        $sftpService->put($this->scheduleExport, $this->filename);

        $this->scheduleExport->update(['last_sent_at' => now()]);
    }
}
