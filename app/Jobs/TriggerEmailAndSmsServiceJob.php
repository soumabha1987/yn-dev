<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AutomatedCommunicationHistoryStatus;
use App\Enums\AutomatedTemplateType;
use App\Enums\CommunicationCode;
use App\Models\AutomatedCommunicationHistory;
use App\Models\AutomationCampaign;
use App\Models\CommunicationStatus;
use App\Models\Consumer;
use App\Services\AutomatedCommunicationHistoryService;
use App\Services\CommunicationStatusService;
use App\Services\TriggerEmailOrSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerEmailAndSmsServiceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Consumer $consumer,
        protected CommunicationCode $communicationCode,
        protected ?AutomationCampaign $automationCampaign = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        AutomatedCommunicationHistoryService $automatedCommunicationHistoryService,
        CommunicationStatusService $communicationStatusService
    ): void {
        $this->consumer->loadMissing(['consumerProfile', 'subclient', 'company']);

        $emailPermission = (bool) $this->consumer->consumerProfile->email_permission;
        $textPermission = (bool) $this->consumer->consumerProfile->text_permission;

        /** @var ?string $email */
        $email = $this->consumer->consumerProfile->email;

        /** @var ?string $mobile */
        $mobile = $this->consumer->consumerProfile->mobile;

        $communicationStatus = $communicationStatusService->findByCode($this->communicationCode);

        if ($emailPermission && $email) {
            $automatedCommunicationHistory = $automatedCommunicationHistoryService
                ->createInProgress($this->consumer, $communicationStatus, AutomatedTemplateType::EMAIL, $this->automationCampaign);

            Log::channel('daily')->info('Sending email..', [
                'communication code' => $this->communicationCode,
                'communication_status_id' => $communicationStatus->id,
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'automated_communication_history_id' => $automatedCommunicationHistory->id,
            ]);

            $this->handleEmailOrSmsService($automatedCommunicationHistory, $communicationStatus, AutomatedTemplateType::EMAIL);
        }

        if ($textPermission && $mobile) {
            $automatedCommunicationHistory = $automatedCommunicationHistoryService
                ->createInProgress($this->consumer, $communicationStatus, AutomatedTemplateType::SMS, $this->automationCampaign);

            Log::channel('daily')->info('Sending sms..', [
                'communication code' => $this->communicationCode,
                'communication_status_id' => $communicationStatus->id,
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'automated_communication_history_id' => $automatedCommunicationHistory->id,
            ]);

            $this->handleEmailOrSmsService($automatedCommunicationHistory, $communicationStatus, AutomatedTemplateType::SMS);
        }
    }

    private function handleEmailOrSmsService(
        AutomatedCommunicationHistory $automatedCommunicationHistory,
        CommunicationStatus $communicationStatus,
        AutomatedTemplateType $type
    ): void {
        $emailOrMobile = $type === AutomatedTemplateType::EMAIL
            ? $this->consumer->consumerProfile->email
            : $this->consumer->consumerProfile->mobile;

        $typeWithEmailOrMobile = ['type' => $type, 'to' => $emailOrMobile];

        $data = app(TriggerEmailOrSmsService::class)->send($this->consumer, $communicationStatus, $typeWithEmailOrMobile);

        if ($data['automated_template'] === null) {
            $automatedCommunicationHistory->update(['status' => AutomatedCommunicationHistoryStatus::FAILED]);

            return;
        }

        $dataNeedsToUpdate = $type === AutomatedTemplateType::EMAIL
            ? ['email' => $data['to']]
            : ['phone' => $data['to']];

        $automatedCommunicationHistory->update([
            'automated_template_id' => $data['automated_template']->id,
            'automated_template_type' => $type,
            'status' => AutomatedCommunicationHistoryStatus::SUCCESS,
            'cost' => $data['cost'],
            ...$dataNeedsToUpdate,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::channel('daily')->error('Sending an email or sms failed', [
            'message' => $exception->getMessage(),
            'stack trace' => $exception->getTrace(),
            'consumer_id' => $this->consumer->id,
            'consumer_profile_id' => $this->consumer->consumer_profile_id,
            'communication code' => $this->communicationCode,
        ]);
    }
}
