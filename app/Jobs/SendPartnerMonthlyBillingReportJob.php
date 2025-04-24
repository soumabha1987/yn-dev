<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PartnerMonthlyReportsMail;
use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPartnerMonthlyBillingReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected Partner $partner,
        protected string $filename
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->partner->report_emails)
            ->bcc([new Address(config('mail.yn_bcc.address'), config('mail.yn_bcc.name', 'YouNegotiate'))])
            ->send(new PartnerMonthlyReportsMail($this->partner, $this->filename));
    }
}
