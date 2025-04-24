<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\ELetterType;
use App\Enums\TemplateType;
use App\Jobs\SendCampaignEmailSmsOrELetterJob;
use App\Mail\AutomatedTemplateMail;
use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Models\CommunicationStatus;
use App\Models\Consumer;
use App\Models\ConsumerELetter;
use App\Models\ConsumerProfile;
use App\Models\ELetter;
use App\Models\Template;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendCampaignEmailSmsOrELetterJobTest extends TestCase
{
    #[Test]
    public function it_can_run_campaign_type_e_letter(): void
    {
        Mail::fake();

        CommunicationStatus::factory()
            ->create([
                'code' => CommunicationCode::ECO_MAIL_RECEIVED,
                'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
            ]);

        $consumers = Consumer::factory(10)
            ->for(ConsumerProfile::factory()->state(['email_permission' => true]))
            ->create();

        $campaignTracker = CampaignTracker::factory()
            ->for(
                $campaign = Campaign::factory()
                    ->for($template = Template::factory()->create(['type' => TemplateType::E_LETTER]))
                    ->create()
            )
            ->create(['delivered_count' => 0]);

        SendCampaignEmailSmsOrELetterJob::dispatchSync($consumers, $campaign, $campaignTracker);

        $this->assertDatabaseHas(ELetter::class, [
            'company_id' => $campaign->company_id,
            'type' => ELetterType::NORMAL,
            'message' => $template->description,
            'disabled' => false,
        ]);

        $this->assertDatabaseCount(ConsumerELetter::class, $consumers->count());

        $this->assertEquals($consumers->count(), $campaignTracker->refresh()->delivered_count);

        Mail::assertQueued(AutomatedTemplateMail::class, $consumers->count());
    }

    #[Test]
    public function it_can_run_campaign_type_email(): void
    {
        Mail::fake();

        $consumers = Consumer::factory(10)
            ->for(ConsumerProfile::factory()->state(['email_permission' => true]))
            ->create();

        $campaignTracker = CampaignTracker::factory()
            ->for(
                $campaign = Campaign::factory()
                    ->for(Template::factory()->create(['type' => TemplateType::EMAIL]))
                    ->create()
            )
            ->create(['delivered_count' => 0]);

        SendCampaignEmailSmsOrELetterJob::dispatchSync($consumers, $campaign, $campaignTracker);

        $this->assertEquals($consumers->count(), $campaignTracker->refresh()->delivered_count);

        Mail::assertQueued(AutomatedTemplateMail::class, $consumers->count());
    }

    #[Test]
    public function it_can_run_campaign_type_email_but_email_permission_false(): void
    {
        Mail::fake();

        $consumers = Consumer::factory(10)
            ->for(ConsumerProfile::factory()->state(['email_permission' => false]))
            ->create();

        $campaignTracker = CampaignTracker::factory()
            ->for(
                $campaign = Campaign::factory()
                    ->for(Template::factory()->create(['type' => TemplateType::EMAIL]))
                    ->create()
            )
            ->create(['delivered_count' => 0]);

        SendCampaignEmailSmsOrELetterJob::dispatchSync($consumers, $campaign, $campaignTracker);

        $this->assertEquals(0, $campaignTracker->refresh()->delivered_count);

        Mail::assertQueued(AutomatedTemplateMail::class, 0);
    }

    #[Test]
    public function it_can_run_campaign_type_sms(): void
    {
        Mail::fake();

        $consumers = Consumer::factory(10)
            ->for(ConsumerProfile::factory()->state(['text_permission' => true]))
            ->create();

        $campaignTracker = CampaignTracker::factory()
            ->for(
                $campaign = Campaign::factory()
                    ->for(Template::factory()->create(['type' => TemplateType::SMS]))
                    ->create()
            )
            ->create(['delivered_count' => 0]);

        Http::fake(fn () => Http::response([
            'data' => [
                'cost' => ['amount' => 5.34],
            ],
        ]));

        SendCampaignEmailSmsOrELetterJob::dispatchSync($consumers, $campaign, $campaignTracker);

        $this->assertEquals($consumers->count(), $campaignTracker->refresh()->delivered_count);

        Mail::assertNotQueued(AutomatedTemplateMail::class);
    }

    #[Test]
    public function it_can_run_campaign_type_sms_with_consumer_text_permission_false(): void
    {
        Mail::fake();

        $consumers = Consumer::factory(10)
            ->for(ConsumerProfile::factory()->state(['text_permission' => false]))
            ->create();

        $campaignTracker = CampaignTracker::factory()
            ->for(
                $campaign = Campaign::factory()
                    ->for(Template::factory()->create(['type' => TemplateType::SMS]))
                    ->create()
            )
            ->create(['delivered_count' => 0]);

        Http::fake(fn () => Http::response([
            'data' => [
                'cost' => ['amount' => 5.34],
            ],
        ]));

        SendCampaignEmailSmsOrELetterJob::dispatchSync($consumers, $campaign, $campaignTracker);

        $this->assertEquals(0, $campaignTracker->refresh()->delivered_count);

        Mail::assertNotQueued(AutomatedTemplateMail::class);
    }
}
