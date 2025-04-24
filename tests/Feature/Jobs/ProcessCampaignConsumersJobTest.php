<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\CampaignFrequency;
use App\Enums\ConsumerStatus;
use App\Enums\GroupConsumerState;
use App\Jobs\ProcessCampaignConsumersJob;
use App\Jobs\SendCampaignEmailSmsOrELetterJob;
use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Models\CampaignTrackerConsumer;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\Group;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessCampaignConsumersJobTest extends TestCase
{
    #[Test]
    public function it_can_run_job(): void
    {
        Queue::fake()->except(ProcessCampaignConsumersJob::class);

        Consumer::factory($processedCount = 10)
            ->for($company = Company::factory()->create())
            ->create(['status' => ConsumerStatus::UPLOADED]);

        $campaign = Campaign::factory()
            ->for(Group::factory()->create(['consumer_state' => GroupConsumerState::ALL_ACTIVE]))
            ->create([
                'company_id' => $company->id,
                'frequency' => CampaignFrequency::ONCE,
                'start_date' => today(),
                'end_date' => null,
            ]);

        $campaignTracker = CampaignTracker::factory()->for($campaign)->create();

        ProcessCampaignConsumersJob::dispatchSync($campaign, $campaignTracker);

        Queue::assertPushed(SendCampaignEmailSmsOrELetterJob::class);

        $this->assertDatabaseCount(CampaignTrackerConsumer::class, $processedCount);
    }

    #[Test]
    public function it_can_run_job_for_more_then_chunk_size_consumers(): void
    {
        Queue::fake()->except(ProcessCampaignConsumersJob::class);

        Consumer::factory($processedCount = 1000)
            ->for($company = Company::factory()->create())
            ->create(['status' => ConsumerStatus::UPLOADED]);

        $campaign = Campaign::factory()
            ->for(Group::factory()->create(['consumer_state' => GroupConsumerState::ALL_ACTIVE]))
            ->create([
                'company_id' => $company->id,
                'frequency' => CampaignFrequency::ONCE,
                'start_date' => today(),
                'end_date' => null,
            ]);

        $campaignTracker = CampaignTracker::factory()->for($campaign)->create();

        ProcessCampaignConsumersJob::dispatchSync($campaign, $campaignTracker);

        Queue::assertPushed(SendCampaignEmailSmsOrELetterJob::class, 2);

        $this->assertDatabaseCount(CampaignTrackerConsumer::class, $processedCount);
    }
}
