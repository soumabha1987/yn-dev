<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\ReprocessMembershipAutoRenewFailedCommand;
use App\Enums\CompanyMembershipStatus;
use App\Jobs\MembershipPlanAutoRenewPaymentJob;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Membership;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReprocessMembershipAutoRenewFailedCommandTest extends TestCase
{
    #[Test]
    public function it_can_render_failed_transaction_reprocess_plan(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['deleted_at' => null]);

        $membership = Membership::factory()->create(['status' => true]);

        CompanyMembership::factory()
            ->for($company)
            ->recycle($membership)
            ->create([
                'status' => CompanyMembershipStatus::INACTIVE,
                'auto_renew' => true,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDays(2),
                'next_membership_plan_id' => null,
                'cancelled_at' => null,
            ]);

        $this->artisan(ReprocessMembershipAutoRenewFailedCommand::class)->assertSuccessful();

        Queue::assertPushed(MembershipPlanAutoRenewPaymentJob::class);
    }
}
