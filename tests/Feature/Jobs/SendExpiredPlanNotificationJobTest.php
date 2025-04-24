<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\CompanyMembershipStatus;
use App\Enums\ConsumerStatus;
use App\Jobs\SendExpiredPlanNotificationJob;
use App\Mail\ExpiredPlanNotificationMail;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\Membership;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendExpiredPlanNotificationJobTest extends TestCase
{
    #[Test]
    public function it_can_render_expired_plan_notification_sending_mail(): void
    {
        Mail::fake();

        $company = Company::factory()->create();

        $membership = Membership::factory()->create(['status' => true]);

        $companyMembership = CompanyMembership::factory()
            ->for($company)
            ->recycle($membership)
            ->create([
                'status' => CompanyMembershipStatus::INACTIVE,
                'auto_renew' => true,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDays(fake()->numberBetween(3, 6)),
                'next_membership_plan_id' => null,
                'cancelled_at' => null,
            ]);

        SendExpiredPlanNotificationJob::dispatchSync($companyMembership);

        Mail::assertQueued(ExpiredPlanNotificationMail::class, function (ExpiredPlanNotificationMail $mail) use ($company): bool {
            return $mail->assertTo($company->owner_email)
                && $mail->assertTo($company->owner_email)->viewData['content'] === __('We wanted to inform you that your plan has expired. To avoid any further disruption in service, please renew your plan at your earliest convenience. If not renewed within 7 days, your consumer account will be deactivated');
        });
    }

    #[Test]
    public function it_can_render_expired_after_week_plan_notification_sending_mail_with_consumer_deactivated(): void
    {
        Mail::fake();

        $company = Company::factory()->create();

        $membership = Membership::factory()->create(['status' => true]);

        $companyMembership = CompanyMembership::factory()
            ->for($company)
            ->recycle($membership)
            ->create([
                'status' => CompanyMembershipStatus::INACTIVE,
                'auto_renew' => true,
                'current_plan_start' => now()->subYear(),
                'current_plan_end' => now()->subWeek(),
                'next_membership_plan_id' => null,
                'cancelled_at' => null,
            ]);

        SendExpiredPlanNotificationJob::dispatchSync($companyMembership);
        $this->assertNull($companyMembership->refresh()->cancelled_at);
        Mail::assertQueued(ExpiredPlanNotificationMail::class);
    }

    #[Test]
    public function it_can_render_expired_after_month_plan_notification_sending_mail_with_consumer_deactivated(): void
    {
        Mail::fake();

        $company = Company::factory()->create();

        Consumer::factory(5)->for($company)->create(['status' => ConsumerStatus::UPLOADED]);

        $membership = Membership::factory()->create(['status' => true]);

        $companyMembership = CompanyMembership::factory()
            ->for($company)
            ->recycle($membership)
            ->create([
                'status' => CompanyMembershipStatus::INACTIVE,
                'auto_renew' => true,
                'current_plan_start' => now()->subYear(),
                'current_plan_end' => now()->subMonths(3),
                'next_membership_plan_id' => null,
                'cancelled_at' => null,
            ]);
        SendExpiredPlanNotificationJob::dispatchSync($companyMembership);

        $this->assertNotNull($companyMembership->refresh()->cancelled_at);

        $this->assertDatabaseMissing(Consumer::class, [
            'company_id' => $company->id,
            'status' => ConsumerStatus::UPLOADED,
        ]);

        $this->assertDatabaseHas(Consumer::class, [
            'company_id' => $company->id,
            'status' => ConsumerStatus::DEACTIVATED,
        ]);
        Mail::assertQueued(ExpiredPlanNotificationMail::class);
    }
}
