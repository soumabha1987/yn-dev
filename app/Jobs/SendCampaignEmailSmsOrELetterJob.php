<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CommunicationCode;
use App\Enums\ELetterType;
use App\Enums\TemplateType;
use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Models\Consumer;
use App\Models\ELetter;
use App\Services\TriggerEmailOrSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendCampaignEmailSmsOrELetterJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected TriggerEmailOrSmsService $triggerEmailOrSmsService;

    protected int $chunkSize;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Collection $consumers,
        protected Campaign $campaign,
        protected CampaignTracker $campaignTracker,
    ) {
        $this->triggerEmailOrSmsService = app(TriggerEmailOrSmsService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        match ($this->campaign->template->type) {
            TemplateType::E_LETTER => $this->sendELetter(),
            TemplateType::EMAIL => $this->sendEmail(),
            TemplateType::SMS => $this->sendSMS(),
        };
    }

    private function sendELetter(): void
    {
        $this->consumers->each(
            function (Consumer $consumer): void {
                TriggerEmailAndSmsServiceJob::dispatch($consumer, CommunicationCode::ECO_MAIL_RECEIVED);
            }
        );

        $eLetter = ELetter::query()
            ->create([
                'company_id' => $this->campaign->company_id,
                'type' => ELetterType::NORMAL,
                'message' => $this->campaign->template->description,
                'disabled' => false,
            ]);

        $eLetter->consumers()->attach($this->consumers->pluck('id')->all(), ['enabled' => true, 'read_by_consumer' => false]);

        $this->campaignTracker->increment('delivered_count', $this->consumers->count());
    }

    private function sendEmail(): void
    {
        $this->consumers->each(function (Consumer $consumer): void {
            $consumer->loadMissing('consumerProfile', 'company', 'subclient');

            if (! $consumer->consumerProfile->email_permission || blank($consumer->consumerProfile->email)) {
                return;
            }

            $this->triggerEmailOrSmsService
                ->sendEmail(
                    $consumer,
                    (string) $consumer->consumerProfile->email,
                    (string) $this->campaign->template->subject,
                    (string) $this->campaign->template->description
                );

            Log::channel('daily')->info('Consumer send Email successfully', [
                'consumer_id' => $consumer->id,
                'campaign_id' => $this->campaign->id,
                'frequency' => $this->campaign->frequency,
            ]);

            $this->campaignTracker->increment('delivered_count');
        });
    }

    private function sendSMS(): void
    {
        $this->consumers->each(function (Consumer $consumer): void {
            $consumer->loadMissing('consumerProfile', 'company', 'subclient');

            if (! $consumer->consumerProfile->text_permission || blank($consumer->consumerProfile->mobile)) {
                return;
            }

            $response = $this->triggerEmailOrSmsService
                ->sendSMS(
                    $consumer,
                    (string) $consumer->consumerProfile->mobile,
                    (string) $this->campaign->template->description
                );

            if ($response->ok()) {
                $consumer->campaignTrackerConsumer()->update(['cost' => $response->json('data.cost.amount')]);

                $this->campaignTracker->increment('delivered_count');

                Log::channel('daily')->info('Consumer send sms successfully', [
                    'consumer_id' => $consumer->id,
                    'campaign_id' => $this->campaign->id,
                    'frequency' => $this->campaign->frequency,
                ]);
            }
        });
    }
}
