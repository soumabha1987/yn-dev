<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\MembershipFrequency;
use App\Enums\MembershipTransactionStatus;
use App\Enums\Role as EnumRole;
use App\Jobs\MembershipPlanAutoRenewPaymentJob;
use App\Livewire\Creditor\ImportConsumers\UploadFilePage;
use App\Mail\ExpiredPlanNotificationMail;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Membership;
use App\Models\MembershipPaymentProfile;
use App\Models\MembershipTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MembershipPlanAutoRenewPaymentJobTest extends TestCase
{
    #[Test]
    public function it_can_renew_the_membership_with_zero_amount(): void
    {
        $this->travelTo(now()->addMinutes(5)->addSeconds(5));

        $company = Company::factory()
            ->create([
                'current_step' => CreditorCurrentStep::COMPLETED->value,
            ]);

        $membership = Membership::factory()->create(['price' => 0, 'status' => true, 'frequency' => MembershipFrequency::YEARLY]);

        $companyMembership = CompanyMembership::factory()
            ->for($company)
            ->recycle($membership)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDay(),
            ]);

        MembershipPlanAutoRenewPaymentJob::dispatchSync($companyMembership);

        $this->assertEquals(CompanyMembershipStatus::ACTIVE, $company->activeCompanyMembership->refresh()->status);
        $this->assertEquals(
            $company->activeCompanyMembership->current_plan_end->toDateTimeString(),
            $company->activeCompanyMembership->current_plan_start->addYear()->toDateTimeString()
        );

        $this->assertDatabaseHas(MembershipTransaction::class, [
            'status' => MembershipTransactionStatus::SUCCESS->value,
            'company_id' => $companyMembership->company_id,
            'membership_id' => $companyMembership->membership_id,
            'price' => number_format(0, 2, thousands_separator: ''),
            'tilled_transaction_id' => null,
        ]);

        $role = Role::query()->create(['name' => EnumRole::CREDITOR->value]);

        $user = User::factory()->for($company)->create();

        $user->assignRole($role);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('creditor.import-consumers.upload-file'))
            ->assertSeeLivewire(UploadFilePage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_automatic_renew_the_current_membership_of_the_company(): void
    {
        $this->travelTo(now()->startOfDay());

        Http::fake(fn () => Http::response(['status' => 'succeeded']));

        $company = Company::factory()->create();

        MembershipPaymentProfile::factory()->create(['company_id' => $company->id]);

        $membership = Membership::factory()->create(['price' => $price = 100.10, 'frequency' => MembershipFrequency::MONTHLY]);

        $companyMembership = CompanyMembership::factory()
            ->for($membership)
            ->for($company)
            ->create([
                'next_membership_plan_id' => null,
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDay(),
            ]);

        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        MembershipPlanAutoRenewPaymentJob::dispatchSync($companyMembership);

        $companyMembership->refresh();

        $this->assertEquals($companyMembership->membership_id, $membership->id);
        $this->assertNull($companyMembership->next_membership_plan_id);

        $this->assertEquals($companyMembership->status, CompanyMembershipStatus::ACTIVE);
        $this->assertEquals(
            $companyMembership->current_plan_end->toDateString(),
            $companyMembership->current_plan_start->addMonthNoOverflow()->toDateString()
        );

        $endDate = match ($membership->frequency) {
            MembershipFrequency::WEEKLY => $companyMembership->current_plan_start->addWeek()->toDateString(),
            MembershipFrequency::MONTHLY => $companyMembership->current_plan_start->addMonthNoOverflow()->toDateString(),
            MembershipFrequency::YEARLY => $companyMembership->current_plan_start->addYear()->toDateString(),
        };

        $this->assertEquals($companyMembership->current_plan_end->toDateString(), $endDate);

        $this->assertDatabaseHas(MembershipTransaction::class, [
            'company_id' => $company->id,
            'membership_id' => $membership->id,
            'status' => MembershipTransactionStatus::SUCCESS,
            'price' => $price,
            'plan_end_date' => $companyMembership->current_plan_end->addMonthNoOverflow(),
        ]);
    }

    #[Test]
    public function it_can_automatic_failed_membership_plan_auto_renew(): void
    {
        Mail::fake();

        $this->travelTo(now()->startOfDay());

        Http::fake(fn () => Http::response(['status' => 'failed']));

        $company = Company::factory()->create();

        MembershipPaymentProfile::factory()->create(['company_id' => $company->id]);

        $membership = Membership::factory()->create(['price' => $price = 100.10]);

        $companyMembership = CompanyMembership::factory()
            ->for($membership)
            ->for($company)
            ->create([
                'next_membership_plan_id' => null,
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDay(),
            ]);

        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        MembershipPlanAutoRenewPaymentJob::dispatchSync($companyMembership);

        $companyMembership->refresh();

        $endDate = match ($membership->frequency) {
            MembershipFrequency::WEEKLY => $companyMembership->current_plan_end->addWeek()->toDateString(),
            MembershipFrequency::MONTHLY => $companyMembership->current_plan_end->addMonthNoOverflow()->toDateString(),
            MembershipFrequency::YEARLY => $companyMembership->current_plan_end->addYear()->toDateString(),
        };

        $this->assertDatabaseHas(MembershipTransaction::class, [
            'company_id' => $company->id,
            'membership_id' => $membership->id,
            'status' => MembershipTransactionStatus::FAILED,
            'price' => $price,
            'plan_end_date' => $endDate,
        ]);

        Mail::assertQueued(ExpiredPlanNotificationMail::class, function (ExpiredPlanNotificationMail $mail) use ($company): bool {
            return $mail->assertTo($company->owner_email)
                && $mail->assertTo($company->owner_email)->viewData['content'] === __('Your membership transaction has failed. We will attempt to reprocess it in 24 hours.');
        });
    }

    #[Test]
    public function it_can_automatic_failed_membership_plan_auto_reprocess(): void
    {
        Mail::fake();

        $this->travelTo(now()->startOfDay());

        Http::fake(fn () => Http::response(['status' => 'failed']));

        $company = Company::factory()->create();

        MembershipPaymentProfile::factory()->create(['company_id' => $company->id]);

        $membership = Membership::factory()->create(['price' => $price = 100.10]);

        $companyMembership = CompanyMembership::factory()
            ->for($membership)
            ->for($company)
            ->create([
                'next_membership_plan_id' => null,
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_start' => now()->subWeek(),
                'current_plan_end' => now()->subDays(2),
            ]);

        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        MembershipPlanAutoRenewPaymentJob::dispatchSync($companyMembership);

        $companyMembership->refresh();

        $endDate = match ($membership->frequency) {
            MembershipFrequency::WEEKLY => $companyMembership->current_plan_end->addWeek()->toDateString(),
            MembershipFrequency::MONTHLY => $companyMembership->current_plan_end->addMonthNoOverflow()->toDateString(),
            MembershipFrequency::YEARLY => $companyMembership->current_plan_end->addYear()->toDateString(),
        };

        $this->assertDatabaseHas(MembershipTransaction::class, [
            'company_id' => $company->id,
            'membership_id' => $membership->id,
            'status' => MembershipTransactionStatus::FAILED,
            'price' => $price,
            'plan_end_date' => $endDate,
        ]);

        Mail::assertQueued(ExpiredPlanNotificationMail::class, function (ExpiredPlanNotificationMail $mail) use ($company): bool {
            return $mail->assertTo($company->owner_email)
                && $mail->assertTo($company->owner_email)->viewData['content'] === __('Your membership transaction has failed. Please update your payment details.');
        });
    }
}
