<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ConsumerStatus;
use App\Mail\ExpiredPlanNotificationMail;
use App\Models\CompanyMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendExpiredPlanNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected CompanyMembership $companyMembership,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $email = $this->companyMembership->company->owner_email;

        if ($this->companyMembership->current_plan_end->gte(now()->subMonth())) {
            $content = __('We wanted to inform you that your plan has expired. To avoid any further disruption in service, please renew your plan at your earliest convenience. If not renewed within 7 days, your consumer account will be deactivated');

            Mail::to($email)->send(new ExpiredPlanNotificationMail($content));

            return;
        }

        $this->companyMembership->company->consumers()->update([
            'status' => ConsumerStatus::DEACTIVATED,
            'disputed_at' => now(),
        ]);

        $this->companyMembership->update(['cancelled_at' => now()]);

        $content = __('Your plan has expired, Your all consumers are deactivated');

        Mail::to($email)->send(new ExpiredPlanNotificationMail($content));
    }
}
