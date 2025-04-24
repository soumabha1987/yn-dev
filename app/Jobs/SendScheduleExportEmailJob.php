<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\ScheduleExportMail;
use App\Models\ScheduleExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendScheduleExportEmailJob implements ShouldQueue
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
    public function handle(): void
    {
        Mail::to($this->scheduleExport->emails)->send(new ScheduleExportMail($this->scheduleExport, $this->filename));

        $this->scheduleExport->update(['last_sent_at' => now()]);
    }
}
