<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\RunCampaignCommand;
use App\Enums\CampaignFrequency;
use App\Enums\ConsumerStatus;
use App\Enums\GroupConsumerState;
use App\Enums\MembershipTransactionStatus;
use App\Jobs\ProcessCampaignConsumersJob;
use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\Group;
use App\Models\MembershipPaymentProfile;
use App\Models\Template;
use App\Models\YnTransaction;
use App\Services\TilledPaymentService;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RunCampaignCommandTest extends TestCase
{
    protected float $ecoMailAmount;

    #[Test]
    public function it_can_run_campaign_command(): void
    {
        Queue::fake();

        $company = $this->companyPaymentSetup();

        Consumer::factory($processedCount = 10)
            ->for($company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Campaign::factory()
            ->for(Group::factory()->create(['consumer_state' => GroupConsumerState::ALL_ACTIVE]))
            ->create([
                'company_id' => $company->id,
                'frequency' => CampaignFrequency::ONCE,
                'start_date' => today(),
                'end_date' => null,
            ]);

        $this->artisan(RunCampaignCommand::class)->assertSuccessful();

        Queue::assertPushed(ProcessCampaignConsumersJob::class);

        $this->assertDatabaseCount(CampaignTracker::class, 1);

        $this->assertDatabaseHas(YnTransaction::class, [
            'company_id' => $company->id,
            'amount' => number_format($processedCount * $this->ecoMailAmount, 2, thousands_separator: ''),
            'email_count' => 0,
            'sms_count' => 0,
            'eletter_count' => $processedCount,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
            'eletter_cost' => number_format($processedCount * $this->ecoMailAmount, 2, thousands_separator: ''),
            'status' => MembershipTransactionStatus::SUCCESS,
        ]);
    }

    #[Test]
    public function it_can_run_campaign_command_with_no_consumer(): void
    {
        Queue::fake();

        Campaign::factory()->create(['frequency' => CampaignFrequency::ONCE, 'start_date' => today(), 'end_date' => null]);

        $this->artisan(RunCampaignCommand::class)->assertSuccessful();

        Queue::assertNotPushed(ProcessCampaignConsumersJob::class);

        $this->assertDatabaseCount(CampaignTracker::class, 0);
    }

    #[Test]
    public function it_can_run_campaign_command_with_once_frequency(): void
    {
        Queue::fake();

        $company = $this->companyPaymentSetup();

        Consumer::factory($processedCount = 10)
            ->for($company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Campaign::factory()
            ->for(Group::factory()->create(['consumer_state' => GroupConsumerState::ALL_ACTIVE]))
            ->forEachSequence(
                ['start_date' => today()],
                ['start_date' => today()->subDay()],
                ['start_date' => today()->addDay()],
                ['start_date' => today()],
            )
            ->create([
                'company_id' => $company->id,
                'frequency' => CampaignFrequency::ONCE,
                'end_date' => null,
            ]);

        $this->artisan(RunCampaignCommand::class)->assertSuccessful();

        Queue::assertPushed(ProcessCampaignConsumersJob::class, 2);

        $this->assertDatabaseCount(CampaignTracker::class, 2);

        $this->assertDatabaseHas(YnTransaction::class, [
            'company_id' => $company->id,
            'amount' => number_format($processedCount * $this->ecoMailAmount, 2, thousands_separator: ''),
            'email_count' => 0,
            'sms_count' => 0,
            'eletter_count' => $processedCount,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
            'eletter_cost' => number_format($processedCount * $this->ecoMailAmount, 2, thousands_separator: ''),
            'status' => MembershipTransactionStatus::SUCCESS,
        ]);

        $this->assertDatabaseCount(YnTransaction::class, 2);
    }

    #[Test]
    public function it_can_run_campaign_command_with_deleted_group(): void
    {
        Queue::fake();

        Consumer::factory(10)
            ->for($company = Company::factory()->create())
            ->create();

        Campaign::factory()
            ->for(Group::factory()->create(['deleted_at' => now()]))
            ->create([
                'company_id' => $company->id,
                'frequency' => CampaignFrequency::ONCE,
                'start_date' => today(),
                'end_date' => null,
            ]);

        $this->artisan(RunCampaignCommand::class)->assertSuccessful();

        Queue::assertNotPushed(ProcessCampaignConsumersJob::class);

        $this->assertDatabaseCount(CampaignTracker::class, 0);
    }

    #[Test]
    public function it_can_run_campaign_command_with_template_group(): void
    {
        Queue::fake();

        Consumer::factory(10)
            ->for($company = Company::factory()->create())
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Campaign::factory()
            ->for(Group::factory()->create(['consumer_state' => GroupConsumerState::ALL_ACTIVE]))
            ->for(Template::factory()->create(['deleted_at' => now()]))
            ->create([
                'company_id' => $company->id,
                'frequency' => CampaignFrequency::ONCE,
                'start_date' => today(),
                'end_date' => null,
            ]);

        $this->artisan(RunCampaignCommand::class)->assertSuccessful();

        Queue::assertNotPushed(ProcessCampaignConsumersJob::class);

        $this->assertDatabaseCount(CampaignTracker::class, 0);
    }

    #[Test]
    public function it_can_run_campaign_command_with_daily_frequency(): void
    {
        Queue::fake();

        $company = $this->companyPaymentSetup();

        Consumer::factory($processedCount = 10)
            ->for($company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Campaign::factory()
            ->for(Group::factory()->create(['consumer_state' => GroupConsumerState::ALL_ACTIVE]))
            ->forEachSequence(
                ['start_date' => today(), 'end_date' => today()->addMonthNoOverflow()],
                ['start_date' => today()->subDay(), 'end_date' => today()],
                ['start_date' => today()->addDay(), 'end_date' => today()->addMonthNoOverflow()],
                ['start_date' => today()->subMonth(), 'end_date' => today()->subDay()],
            )
            ->create(
                [
                    'company_id' => $company->id,
                    'frequency' => CampaignFrequency::DAILY]
            );

        $this->artisan(RunCampaignCommand::class)->assertSuccessful();

        Queue::assertPushed(ProcessCampaignConsumersJob::class, 2);

        $this->assertDatabaseCount(CampaignTracker::class, 2);

        $this->assertDatabaseHas(YnTransaction::class, [
            'company_id' => $company->id,
            'amount' => number_format($processedCount * $this->ecoMailAmount, 2, thousands_separator: ''),
            'email_count' => 0,
            'sms_count' => 0,
            'eletter_count' => $processedCount,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
            'eletter_cost' => number_format($processedCount * $this->ecoMailAmount, 2, thousands_separator: ''),
            'status' => MembershipTransactionStatus::SUCCESS,
        ]);

        $this->assertDatabaseCount(YnTransaction::class, 2);
    }

    #[Test]
    public function it_can_run_campaign_command_with_weekly_frequency(): void
    {
        Queue::fake();

        $company = $this->companyPaymentSetup();

        Consumer::factory($processedCount = 10)
            ->for($company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Campaign::factory()
            ->for(Group::factory()->create(['consumer_state' => GroupConsumerState::ALL_ACTIVE]))
            ->forEachSequence(
                [
                    'start_date' => today(),
                    'end_date' => today()->addMonth(),
                    'day_of_week' => today()->format('w'),
                ],
                [
                    'start_date' => today()->subDay(),
                    'end_date' => today(),
                    'day_of_week' => today()->subDay()->format('w'),
                ],
                [
                    'start_date' => today()->addDay(),
                    'end_date' => today()->addMonth(),
                    'day_of_week' => today()->addDay()->format('w'),
                ],
                [
                    'start_date' => today()->subMonth(),
                    'end_date' => today()->subDay(),
                    'day_of_week' => today()->format('w'),
                ],
            )
            ->create([
                'company_id' => $company->id,
                'frequency' => CampaignFrequency::WEEKLY,
            ]);

        $this->artisan(RunCampaignCommand::class)->assertSuccessful();

        Queue::assertPushed(ProcessCampaignConsumersJob::class, 1);

        $this->assertDatabaseCount(CampaignTracker::class, 1);

        $this->assertDatabaseHas(YnTransaction::class, [
            'company_id' => $company->id,
            'amount' => number_format($processedCount * $this->ecoMailAmount, 2, thousands_separator: ''),
            'email_count' => 0,
            'sms_count' => 0,
            'eletter_count' => $processedCount,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
            'eletter_cost' => number_format($processedCount * $this->ecoMailAmount, 2, thousands_separator: ''),
            'status' => MembershipTransactionStatus::SUCCESS,
        ]);

        $this->assertDatabaseCount(YnTransaction::class, 1);
    }

    #[Test]
    public function it_can_run_campaign_command_with_monthly_frequency(): void
    {
        Queue::fake();

        $company = $this->companyPaymentSetup();

        Consumer::factory($processedCount = 10)
            ->for($company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Campaign::factory()
            ->for(Group::factory()->create(['consumer_state' => GroupConsumerState::ALL_ACTIVE]))
            ->forEachSequence(
                [
                    'start_date' => today(),
                    'end_date' => today()->addMonth(),
                    'day_of_month' => today()->format('d'),
                ],
                [
                    'start_date' => today()->subDay(),
                    'end_date' => today(),
                    'day_of_month' => today()->subDay()->format('d'),
                ],
                [
                    'start_date' => today()->addDay(),
                    'end_date' => today()->addMonth(),
                    'day_of_month' => today()->addDay()->format('d'),
                ],
                [
                    'start_date' => today()->subMonth(),
                    'end_date' => today()->subDay(),
                    'day_of_month' => today()->format('d'),
                ],
            )
            ->create([
                'company_id' => $company->id,
                'frequency' => CampaignFrequency::MONTHLY,
            ]);

        $this->artisan(RunCampaignCommand::class)->assertSuccessful();

        Queue::assertPushed(ProcessCampaignConsumersJob::class, 1);

        $this->assertDatabaseCount(CampaignTracker::class, 1);

        $this->assertDatabaseHas(YnTransaction::class, [
            'company_id' => $company->id,
            'amount' => number_format($processedCount * $this->ecoMailAmount, 2, thousands_separator: ''),
            'email_count' => 0,
            'sms_count' => 0,
            'eletter_count' => $processedCount,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
            'eletter_cost' => number_format($processedCount * $this->ecoMailAmount, 2, thousands_separator: ''),
            'status' => MembershipTransactionStatus::SUCCESS,
        ]);

        $this->assertDatabaseCount(YnTransaction::class, 1);
    }

    private function companyPaymentSetup(): Company
    {
        $company = Company::factory()->create();

        $companyMembership = CompanyMembership::factory()->create(['company_id' => $company->id]);

        $this->ecoMailAmount = (float) $companyMembership->membership->e_letter_fee;

        $this->partialMock(TilledPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createPaymentIntents')
                ->withAnyArgs()
                ->andReturn(['status' => 'succeeded']);
        });

        MembershipPaymentProfile::factory()->create(['company_id' => $company->id]);

        return $company;
    }
}
