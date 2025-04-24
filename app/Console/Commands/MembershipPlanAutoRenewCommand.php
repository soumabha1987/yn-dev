<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CompanyMembershipStatus;
use App\Jobs\MembershipPlanAutoRenewPaymentJob;
use App\Models\CompanyMembership;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPlanAutoRenewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plan:auto-renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Company membership plan automatic renew';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        CompanyMembership::query()
            ->with('membership', 'nextMembershipPlan')
            ->withWhereHas('company', function (Builder|BelongsTo $query): void {
                $query->whereNull('deleted_at');
            })
            ->where('auto_renew', true)
            ->where('current_plan_end', '<', now()->addHours(12))
            ->where('status', CompanyMembershipStatus::ACTIVE)
            ->where(function (Builder $query): void {
                $query->whereNull('next_membership_plan_id')
                    ->orWhere(function (Builder $query): void {
                        $query->whereNotNull('next_membership_plan_id')
                            ->whereHas('nextMembershipPlan', fn (Builder $query) => $query->where('status', true));
                    });
            })
            ->whereHas('membership', fn (Builder $query) => $query->where('status', true))
            ->each(function (CompanyMembership $companyMembership): void {
                MembershipPlanAutoRenewPaymentJob::dispatch($companyMembership);
            });
    }
}
