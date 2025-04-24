<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CompanyMembershipStatus;
use App\Jobs\SendExpiredPlanNotificationJob;
use App\Models\CompanyMembership;
use Illuminate\Console\Command;

class SendExpiredPlanNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:expire-plan-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends email notifications to creditor for expired membership plans.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        CompanyMembership::query()
            ->withWhereHas('company')
            ->where('auto_renew', true)
            ->where('current_plan_end', '<', now()->subDays(2))
            ->where('status', CompanyMembershipStatus::INACTIVE)
            ->whereNull('next_membership_plan_id')
            ->whereNull('cancelled_at')
            ->each(SendExpiredPlanNotificationJob::dispatch(...));
    }
}
