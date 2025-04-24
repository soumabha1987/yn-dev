<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\SendExpiredPlanNotificationCommand;
use App\Enums\CompanyMembershipStatus;
use App\Jobs\SendExpiredPlanNotificationJob;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Membership;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendExpiredPlanNotificationCommandTest extends TestCase
{
    #[Test]
    public function it_can_send_plan_expired_mail(): void
    {
        Queue::fake();

        $company = Company::factory()->create();

        $membership = Membership::factory()->create(['status' => true]);

        CompanyMembership::factory()
            ->for($company)
            ->recycle($membership)
            ->create([
                'status' => CompanyMembershipStatus::INACTIVE,
                'auto_renew' => true,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDays(3),
                'next_membership_plan_id' => null,
            ]);

        $this->artisan(SendExpiredPlanNotificationCommand::class)->assertSuccessful();

        Queue::assertPushed(SendExpiredPlanNotificationJob::class);
    }

    #[Test]
    public function it_can_render_plan_cancelled(): void
    {
        Queue::fake();

        $company = Company::factory()->create();

        $membership = Membership::factory()->create(['status' => true]);

        CompanyMembership::factory()
            ->for($company)
            ->recycle($membership)
            ->create([
                'status' => CompanyMembershipStatus::INACTIVE,
                'auto_renew' => true,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDays(3),
                'next_membership_plan_id' => null,
                'cancelled_at' => now(),
            ]);

        $this->artisan(SendExpiredPlanNotificationCommand::class)->assertSuccessful();

        Queue::assertNothingPushed();
    }
}
