<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\RunScheduledAutomationCampaignCommand;
use App\Enums\AutomationCampaignFrequency;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\ConsumerStatus;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\AutomationCampaign;
use App\Models\CommunicationStatus;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\ConsumerUnsubscribe;
use App\Services\TriggerEmailOrSmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RunScheduledAutomationCampaignCommandTest extends TestCase
{
    #[Test]
    public function it_can_not_run_when_it_has_no_automation_campaign(): void
    {
        Log::shouldReceive('channel')->once()->with('daily')->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->withAnyArgs()
            ->andReturnNull();

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();
    }

    #[Test]
    public function it_can_send_only_those_consumer_which_have_counter_offer_but_no_payment_setup(): void
    {
        Queue::fake();

        $this->travelTo(now()->addDay());

        Log::shouldReceive('channel')->twice()->with('daily')->andReturnSelf();
        Log::shouldReceive('info')
            ->twice()
            ->withAnyArgs()
            ->andReturnNull();

        $automationCampaign = $this->prepareEnableAutomationCampaign(CommunicationCode::OFFER_APPROVED_BUT_NO_PAYMENT_SETUP);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state([
                'text_permission' => true,
                'email_permission' => true,
            ]))
            ->activeMembershipCompany()
            ->create([
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => false,
            ]);

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job) => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 1);

        $this->assertEquals(now()->toDateTimeString(), $automationCampaign->refresh()->last_sent_at);
    }

    #[Test]
    public function it_can_send_only_those_consumer_which_have_offer_approved_but_no_payment_setup(): void
    {
        Queue::fake();

        $this->travelTo(now()->addDay());

        Log::shouldReceive('channel')->twice()->with('daily')->andReturnSelf();
        Log::shouldReceive('info')
            ->twice()
            ->withAnyArgs()
            ->andReturnNull();

        $automationCampaign = $this->prepareEnableAutomationCampaign(CommunicationCode::OFFER_APPROVED_BUT_NO_PAYMENT_SETUP);

        $consumerProfile = ConsumerProfile::factory()->create([
            'text_permission' => true,
            'email_permission' => true,
        ]);

        $consumer = Consumer::factory()->activeMembershipCompany()->create([
            'consumer_profile_id' => $consumerProfile->id,
            'offer_accepted' => true,
            'payment_setup' => false,
        ]);

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 1);

        $this->assertEquals(now()->toDateTimeString(), $automationCampaign->refresh()->last_sent_at);
    }

    #[Test]
    public function it_can_send_only_those_consumer_which_have_offer_denied(): void
    {
        Queue::fake();

        $this->travelTo(now()->addDay());

        Log::shouldReceive('channel')->twice()->with('daily')->andReturnSelf();
        Log::shouldReceive('info')
            ->twice()
            ->withAnyArgs()
            ->andReturnNull();

        $automationCampaign = $this->prepareEnableAutomationCampaign(CommunicationCode::OFFER_DECLINED);

        $consumerProfile = ConsumerProfile::factory()->create([
            'text_permission' => true,
            'email_permission' => true,
        ]);

        $consumer = Consumer::factory()->activeMembershipCompany()->create([
            'consumer_profile_id' => $consumerProfile->id,
            'status' => ConsumerStatus::PAYMENT_DECLINED->value,
            'counter_offer' => false,
        ]);

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 1);

        $this->assertEquals(now()->toDateTimeString(), $automationCampaign->refresh()->last_sent_at);
    }

    #[Test]
    public function it_can_not_pushed_the_job_on_queue_if_consumer_is_not_found_for_given_conditions(): void
    {
        Queue::fake();

        $this->prepareEnableAutomationCampaign();

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        // Because of there is a communication code which is not matched with our conditions.
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_can_run_automation_campaign_only_once(): void
    {
        Queue::fake();

        $this->travelTo(now()->addDay());

        Log::shouldReceive('channel')->times(3)->with('daily')->andReturnSelf();
        Log::shouldReceive('info')
            ->times(3)
            ->withAnyArgs()
            ->andReturnNull();

        $this->prepareEnableAutomationCampaign(CommunicationCode::COUNTER_OFFER_BUT_NO_RESPONSE);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state([
                'text_permission' => true,
                'email_permission' => true,
            ]))
            ->activeMembershipCompany()
            ->create([
                'offer_accepted' => false,
                'counter_offer' => true,
            ]);

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 1);

        // This will check second time job is pushed because we are using once!
        $this->artisan(RunScheduledAutomationCampaignCommand::class);

        Queue::assertNotPushed(TriggerEmailOrSmsService::class);
    }

    #[Test]
    public function it_can_run_automation_campaign_hourly(): void
    {
        Queue::fake();

        $automationCampaign = $this->prepareEnableAutomationCampaign(CommunicationCode::COUNTER_OFFER_BUT_NO_RESPONSE);

        $automationCampaign->update([
            'frequency' => AutomationCampaignFrequency::HOURLY,
            'hourly' => 2,
        ]);

        $consumerProfile = ConsumerProfile::factory()->create([
            'text_permission' => true,
            'email_permission' => true,
        ]);

        $consumer = Consumer::factory()
            ->activeMembershipCompany()
            ->create([
                'consumer_profile_id' => $consumerProfile->id,
                'offer_accepted' => false,
                'counter_offer' => true,
            ]);

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        $this->assertNotNull($automationCampaign->refresh()->last_sent_at);

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );

        Queue::fake();

        $this->travelTo(now()->addHours(2));

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );
    }

    #[Test]
    public function it_can_run_automation_campaign_daily(): void
    {
        Queue::fake();

        $automationCampaign = $this->prepareEnableAutomationCampaign(CommunicationCode::COUNTER_OFFER_BUT_NO_RESPONSE);

        $automationCampaign->update(['frequency' => AutomationCampaignFrequency::DAILY]);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state([
                'text_permission' => true,
                'email_permission' => true,
            ]))
            ->activeMembershipCompany()
            ->create([
                'offer_accepted' => false,
                'counter_offer' => true,
            ]);

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        $this->assertNotNull($automationCampaign->refresh()->last_sent_at);

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );

        Queue::fake();

        $this->travelTo(now()->addDay());

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );
    }

    #[Test]
    public function it_can_run_automation_campaign_weekly(): void
    {
        Queue::fake();

        $automationCampaign = $this->prepareEnableAutomationCampaign(CommunicationCode::OFFER_DECLINED);

        $automationCampaign->update([
            'frequency' => AutomationCampaignFrequency::WEEKLY,
            'weekly' => now()->dayOfWeek,
        ]);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state([
                'text_permission' => true,
                'email_permission' => true,
            ]))
            ->activeMembershipCompany()
            ->create([
                'status' => ConsumerStatus::PAYMENT_DECLINED,
                'counter_offer' => false,
            ]);

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        $this->assertNotNull($automationCampaign->refresh()->last_sent_at);

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );

        Queue::fake();

        $this->travelTo(now()->addWeek());

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );
    }

    #[Test]
    public function it_can_run_automation_campaign_monthly(): void
    {
        Queue::fake();

        $automationCampaign = $this->prepareEnableAutomationCampaign(CommunicationCode::OFFER_APPROVED_BUT_NO_PAYMENT_SETUP);

        $automationCampaign->update([
            'frequency' => AutomationCampaignFrequency::MONTHLY,
            'start_at' => now(),
        ]);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state([
                'text_permission' => true,
                'email_permission' => true,
            ]))
            ->activeMembershipCompany()
            ->create([
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => false,
            ]);

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        $this->assertNotNull($automationCampaign->refresh()->last_sent_at);

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );

        Queue::fake();

        $this->travelTo(now()->addMonth());

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
        );
    }

    #[Test]
    public function it_can_not_run_automation_campaign_if_no_active_membership_of_company(): void
    {
        Queue::fake();

        $automationCampaign = $this->prepareEnableAutomationCampaign(CommunicationCode::COUNTER_OFFER_BUT_NO_RESPONSE);

        $automationCampaign->update([
            'frequency' => AutomationCampaignFrequency::MONTHLY,
            'start_at' => now(),
        ]);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state([
                'text_permission' => true,
                'email_permission' => true,
            ]))
            ->create([
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => false,
            ]);

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        $this->assertNotNull($automationCampaign->refresh()->last_sent_at);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_can_send_unsubscribe_consumer(): void
    {
        Queue::fake();

        $this->travelTo(now()->addDay());

        $this->prepareEnableAutomationCampaign(CommunicationCode::OFFER_DECLINED);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state([
                'text_permission' => true,
                'email_permission' => true,
            ]))
            ->activeMembershipCompany()
            ->create([
                'status' => ConsumerStatus::PAYMENT_DECLINED,
                'counter_offer' => false,
            ]);

        ConsumerUnsubscribe::factory()->create([
            'consumer_id' => $consumer->id,
            'company_id' => $consumer->company_id,
            'email' => $consumer->email1,
        ]);

        Log::shouldReceive('channel')->once()->with('daily')->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->withAnyArgs()
            ->andReturnNull();

        $this->artisan(RunScheduledAutomationCampaignCommand::class)->assertOk();

        Queue::assertNothingPushed();
    }

    private function prepareEnableAutomationCampaign(?CommunicationCode $code = null): AutomationCampaign
    {
        return AutomationCampaign::factory()
            ->for(CommunicationStatus::factory()->state([
                'code' => $code ?: CommunicationCode::NEW_ACCOUNT,
                'trigger_type' => CommunicationStatusTriggerType::SCHEDULED,
            ]))
            ->create([
                'frequency' => AutomationCampaignFrequency::ONCE,
                'enabled' => true,
                'start_at' => now()->toDateTimeString(),
                'last_sent_at' => null,
            ]);
    }
}
