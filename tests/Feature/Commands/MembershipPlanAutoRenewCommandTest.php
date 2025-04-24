<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\MembershipPlanAutoRenewCommand;
use App\Enums\CompanyMembershipStatus;
use App\Jobs\MembershipPlanAutoRenewPaymentJob;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Membership;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MembershipPlanAutoRenewCommandTest extends TestCase
{
    #[Test]
    public function it_can_automatic_renew_the_current_membership_of_the_company(): void
    {
        Queue::fake();

        $membership = Membership::factory()->create();

        CompanyMembership::factory()
            ->for($membership)
            ->create([
                'next_membership_plan_id' => null,
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDay(),
            ]);

        $this->artisan(MembershipPlanAutoRenewCommand::class)->assertSuccessful();

        Queue::assertPushed(MembershipPlanAutoRenewPaymentJob::class);
    }

    #[Test]
    public function it_can_allow_to_renew_also_different_membership_which_is_available_in_next_membership(): void
    {
        Queue::fake();

        $company = Company::factory()->create();

        [$oldMembership, $newMembership] = Membership::factory(2)->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($oldMembership)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'next_membership_plan_id' => $newMembership->id,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDay(),
            ]);

        $this->artisan(MembershipPlanAutoRenewCommand::class)->assertSuccessful();

        Queue::assertPushed(MembershipPlanAutoRenewPaymentJob::class);
    }

    #[Test]
    public function it_can_allow_free_membership_to_end_user(): void
    {
        Queue::fake();

        $company = Company::factory()->create();

        $membership = Membership::factory()->create(['price' => 0, 'status' => true]);

        CompanyMembership::factory()
            ->for($company)
            ->recycle($membership)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDay(),
            ]);

        $this->artisan(MembershipPlanAutoRenewCommand::class)->assertSuccessful();

        Queue::assertPushed(MembershipPlanAutoRenewPaymentJob::class);
    }
}
