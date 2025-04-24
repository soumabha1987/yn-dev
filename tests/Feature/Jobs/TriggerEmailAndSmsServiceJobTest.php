<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\AdminConfigurationSlug;
use App\Enums\AutomatedCommunicationHistoryStatus;
use App\Enums\AutomatedTemplateType;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Mail\AutomatedTemplateMail;
use App\Models\AdminConfiguration;
use App\Models\AutomatedCommunicationHistory;
use App\Models\CommunicationStatus;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TriggerEmailAndSmsServiceJobTest extends TestCase
{
    protected Consumer $consumer;

    protected CommunicationStatus $communicationStatus;

    protected AutomatedCommunicationHistory $automatedCommunicationHistory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state([
                'email_permission' => true,
                'text_permission' => true,
            ]))
            ->create();

        $this->communicationStatus = CommunicationStatus::factory()->create();
    }

    #[Test]
    public function it_can_send_email_and_sms_both(): void
    {
        Mail::fake();

        Http::fake(fn () => Http::response([
            'data' => [
                'cost' => ['amount' => 5.34],
            ],
        ]));

        Log::shouldReceive('channel')->with('daily')->andReturnSelf();

        Log::shouldReceive('info')->times(3)->withAnyArgs()->andReturnUndefined();

        AdminConfiguration::query()->create([
            'name' => AdminConfigurationSlug::EMAIL_RATE->displayName(),
            'slug' => AdminConfigurationSlug::EMAIL_RATE,
            'value' => '2.34',
        ]);

        TriggerEmailAndSmsServiceJob::dispatchSync($this->consumer, $this->communicationStatus->code);

        Mail::assertQueued(
            AutomatedTemplateMail::class,
            fn (AutomatedTemplateMail $mail): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($mail))
        );

        $this->assertDatabaseCount(AutomatedCommunicationHistory::class, 2);

        $this->assertDatabaseHas(AutomatedCommunicationHistory::class, [
            'company_id' => $this->consumer->company_id,
            'consumer_id' => $this->consumer->id,
            'status' => AutomatedCommunicationHistoryStatus::SUCCESS,
            'automated_template_type' => AutomatedTemplateType::EMAIL,
            'cost' => '2.34',
            'communication_status_id' => $this->communicationStatus->id,
            'phone' => null,
            'email' => $this->consumer->consumerProfile->email,
        ]);

        $this->assertDatabaseHas(AutomatedCommunicationHistory::class, [
            'company_id' => $this->consumer->company_id,
            'consumer_id' => $this->consumer->id,
            'status' => AutomatedCommunicationHistoryStatus::SUCCESS,
            'automated_template_type' => AutomatedTemplateType::SMS,
            'cost' => '5.34',
            'communication_status_id' => $this->communicationStatus->id,
            'phone' => $this->consumer->consumerProfile->mobile,
            'email' => null,
        ]);
    }

    #[Test]
    #[DataProvider('sendEmailData')]
    public function it_can_send_email(array $data): void
    {
        Mail::fake();

        $this->consumer->consumerProfile()->update($data);

        AdminConfiguration::query()->create([
            'name' => AdminConfigurationSlug::EMAIL_RATE->displayName(),
            'slug' => AdminConfigurationSlug::EMAIL_RATE,
            'value' => '2.34',
        ]);

        Log::shouldReceive('channel')->with('daily')->andReturnSelf();

        Log::shouldReceive('info')->once()->withAnyArgs()->andReturnUndefined();

        TriggerEmailAndSmsServiceJob::dispatchSync($this->consumer, $this->communicationStatus->code);

        $this->assertDatabaseCount(AutomatedCommunicationHistory::class, 1);

        Mail::assertQueued(
            AutomatedTemplateMail::class,
            fn (AutomatedTemplateMail $mail): bool => $this->consumer->is((fn () => $this->{'consumer'})->call($mail))
        );

        $this->assertDatabaseHas(AutomatedCommunicationHistory::class, [
            'company_id' => $this->consumer->company_id,
            'consumer_id' => $this->consumer->id,
            'status' => AutomatedCommunicationHistoryStatus::SUCCESS,
            'automated_template_type' => AutomatedTemplateType::EMAIL,
            'cost' => '2.34',
            'communication_status_id' => $this->communicationStatus->id,
            'phone' => null,
            'email' => $this->consumer->consumerProfile->email,
        ]);
    }

    #[Test]
    #[DataProvider('sendSMSData')]
    public function it_can_send_sms(array $data): void
    {
        Mail::fake();

        putenv('APP_ENV=production');

        $this->consumer->consumerProfile()->update($data);

        Http::fake(fn () => Http::response([
            'data' => [
                'cost' => ['amount' => 5.34],
            ],
        ]));

        Log::shouldReceive('channel')->with('daily')->andReturnSelf();

        Log::shouldReceive('info')->twice()->withAnyArgs()->andReturnUndefined();

        TriggerEmailAndSmsServiceJob::dispatchSync($this->consumer, $this->communicationStatus->code);

        $this->assertDatabaseCount(AutomatedCommunicationHistory::class, 1);

        Mail::assertNothingQueued();

        $this->assertDatabaseHas(AutomatedCommunicationHistory::class, [
            'company_id' => $this->consumer->company_id,
            'consumer_id' => $this->consumer->id,
            'status' => AutomatedCommunicationHistoryStatus::SUCCESS,
            'automated_template_type' => AutomatedTemplateType::SMS,
            'cost' => '5.34',
            'communication_status_id' => $this->communicationStatus->id,
            'phone' => $this->consumer->consumerProfile->mobile,
            'email' => null,
        ]);
    }

    #[Test]
    public function it_can_go_to_both_permission_off(): void
    {
        Mail::fake();

        $this->consumer->consumerProfile()->update([
            'text_permission' => false,
            'email_permission' => false,
        ]);

        Log::shouldReceive('channel')->with('daily')->never()->andReturnSelf();
        Log::shouldReceive('info')->never()->withAnyArgs();

        TriggerEmailAndSmsServiceJob::dispatchSync($this->consumer, $this->communicationStatus->code);

        Mail::assertNothingQueued();

        $this->assertDatabaseCount(AutomatedCommunicationHistory::class, 0);
    }

    public static function sendEmailData(): array
    {
        return [
            [
                [
                    'mobile' => null,
                    'text_permission' => true,
                    'email_permission' => true,
                ],
            ],
            [
                [
                    'text_permission' => false,
                    'email_permission' => true,
                ],
            ],
        ];
    }

    public static function sendSMSData(): array
    {
        return [
            [
                [
                    'email' => null,
                    'text_permission' => true,
                    'email_permission' => true,
                ],
            ],
            [
                [
                    'text_permission' => true,
                    'email_permission' => false,
                ],
            ],
        ];
    }
}
