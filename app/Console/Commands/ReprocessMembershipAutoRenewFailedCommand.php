<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CompanyMembershipStatus;
use App\Jobs\MembershipPlanAutoRenewPaymentJob;
use App\Models\CompanyMembership;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReprocessMembershipAutoRenewFailedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reprocess:failed-auto-renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess failed auto renew membership plan after 24 hours current plan end date';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        CompanyMembership::query()
            ->withWhereHas('company')
            ->where('auto_renew', true)
            ->whereBetween('current_plan_end', [now()->subDays(30), now()->subDay()])
            ->where('status', CompanyMembershipStatus::INACTIVE)
            ->whereNull('next_membership_plan_id')
            ->whereNull('cancelled_at')
            ->whereHas('membership', fn (BelongsTo|Builder $query) => $query->where('status', true))
            ->each(MembershipPlanAutoRenewPaymentJob::dispatch(...));
    }
}
