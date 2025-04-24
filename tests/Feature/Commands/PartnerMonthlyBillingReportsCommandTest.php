<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\PartnerMonthlyBillingReportsCommand;
use App\Enums\MembershipTransactionStatus;
use App\Jobs\DeletePartnerMonthlyBillingReportJob;
use App\Jobs\SendPartnerMonthlyBillingReportJob;
use App\Models\Company;
use App\Models\MembershipTransaction;
use App\Models\Partner;
use App\Models\YnTransaction;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PartnerMonthlyBillingReportsCommandTest extends TestCase
{
    #[Test]
    public function it_can_command_executes_successfully(): void
    {
        $this->artisan(PartnerMonthlyBillingReportsCommand::class)
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_send_mail_partner_monthly_report_successfully(): void
    {
        Bus::fake();

        $company = Company::factory()
            ->for(Partner::factory()->create())
            ->create();

        YnTransaction::factory(10)
            ->sequence(fn (Sequence $sequence): array => ['created_at' => today()->subDays(2 + $sequence->index)])
            ->create([
                'company_id' => $company->id,
                'status' => MembershipTransactionStatus::SUCCESS,
            ]);

        MembershipTransaction::factory(15)
            ->sequence(fn (Sequence $sequence): array => ['created_at' => today()->subDays(2 + $sequence->index)])
            ->create([
                'company_id' => $company->id,
                'status' => MembershipTransactionStatus::SUCCESS,
            ]);

        $this->artisan(PartnerMonthlyBillingReportsCommand::class)->assertOk();

        Bus::assertChained([
            SendPartnerMonthlyBillingReportJob::class,
            DeletePartnerMonthlyBillingReportJob::class,
        ]);
    }

    #[Test]
    public function it_can_send_mail_multiple_partner_report_with_membership_transactions(): void
    {
        Bus::fake();

        MembershipTransaction::factory()
            ->forEachSequence(
                [
                    'company_id' => Company::factory()->for($partner = Partner::factory()->create())->state([]),
                    'created_at' => today()->subDays(10),
                ],
                [
                    'company_id' => Company::factory()->for(Partner::factory()->create())->state([]),
                    'created_at' => today(),
                ],
            )
            ->create(['status' => MembershipTransactionStatus::SUCCESS]);

        $this->artisan(PartnerMonthlyBillingReportsCommand::class)->assertOk();

        Bus::assertChained([
            SendPartnerMonthlyBillingReportJob::class,
            DeletePartnerMonthlyBillingReportJob::class,
        ]);
    }

    #[Test]
    public function it_can_send_mail_multiple_partner_report_with_failed_transaction(): void
    {
        Bus::fake();

        MembershipTransaction::factory()
            ->forEachSequence(
                [
                    'company_id' => Company::factory()->for($partner = Partner::factory()->create())->state([]),
                    'created_at' => today()->subDays(10),
                    'status' => MembershipTransactionStatus::SUCCESS,
                ],
                [
                    'company_id' => Company::factory()->for(Partner::factory()->create())->state([]),
                    'created_at' => today()->subDays(10),
                    'status' => MembershipTransactionStatus::FAILED,
                ],
            )
            ->create();

        $this->artisan(PartnerMonthlyBillingReportsCommand::class)->assertOk();

        Bus::assertChained([
            SendPartnerMonthlyBillingReportJob::class,
            DeletePartnerMonthlyBillingReportJob::class,
        ]);
    }
}
