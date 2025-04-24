<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AutomationCampaignFrequency;
use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\AutomationCampaign;
use App\Models\Consumer;
use App\Services\AutomationCampaignService;
use App\Services\ConsumerService;
use App\Services\TriggerEmailOrSmsService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledAutomationCampaignCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:schedule-automation-campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled automation campaigns';

    /**
     * Execute the console command.
     */
    public function handle(
        AutomationCampaignService $automationCampaignService,
        ConsumerService $consumerService,
        TriggerEmailOrSmsService $triggerEmailOrSmsService
    ): void {
        Log::channel('daily')->info('Scheduled automation campaign command started at ' . now()->format('d F Y, H:i:s'));

        $automationCampaigns = $automationCampaignService->fetchEnabled();

        try {
            $automationCampaigns->each(function (AutomationCampaign $automationCampaign) use ($consumerService) {
                [$firedAutomationCampaign, $campaign] = match ($automationCampaign->frequency) {
                    AutomationCampaignFrequency::ONCE => [$this->shouldRunOneTimeCampaign($automationCampaign), 'One time'],
                    AutomationCampaignFrequency::HOURLY => [$this->shouldRunHourlyCampaign($automationCampaign), 'Hourly'],
                    AutomationCampaignFrequency::DAILY => [$this->shouldRunDailyCampaign($automationCampaign), 'Daily'],
                    AutomationCampaignFrequency::WEEKLY => [$this->shouldRunWeeklyCampaign($automationCampaign), 'Weekly'],
                    AutomationCampaignFrequency::MONTHLY => [$this->shouldRunMonthlyCampaign($automationCampaign), 'Monthly'],
                };

                if ($firedAutomationCampaign) {
                    $consumerStatusConditions = match ($automationCampaign->communicationStatus->code) {
                        CommunicationCode::COUNTER_OFFER_BUT_NO_RESPONSE => ['offer_accepted' => 0, 'counter_offer' => 1],
                        CommunicationCode::OFFER_APPROVED_BUT_NO_PAYMENT_SETUP => ['offer_accepted' => 1, 'payment_setup' => 0],
                        CommunicationCode::OFFER_DECLINED => ['status' => ConsumerStatus::PAYMENT_DECLINED->value, 'counter_offer' => 0],
                        default => [],
                    };

                    if (blank($consumerStatusConditions)) {
                        return true;
                    }

                    $consumers = $consumerService->fetchConsumersByStatus($consumerStatusConditions);

                    $consumers->each(function (Consumer $consumer) use ($automationCampaign, $campaign) {
                        Log::channel('daily')->info('Run schedule automation', [
                            'campaign' => $campaign,
                            'consumer' => $consumer->id,
                            'communication status code' => $automationCampaign->communicationStatus->code,
                        ]);

                        TriggerEmailAndSmsServiceJob::dispatch($consumer, $automationCampaign->communicationStatus->code, $automationCampaign);
                    });

                    $automationCampaign->update(['last_sent_at' => now()]);
                }

                return true;
            });
        } catch (Exception $exception) {
            Log::channel('daily')->error('Run schedule automation failed', [
                'message' => $exception->getMessage(),
                'status code' => $exception->getCode(),
                'stack trace' => $exception->getTrace(),
            ]);
        }
    }

    private function shouldRunOneTimeCampaign(AutomationCampaign $automationCampaign): bool
    {
        $isAlreadySendEmailOrSms = $automationCampaign->last_sent_at !== null;

        if ($isAlreadySendEmailOrSms) {
            return false;
        }

        return now()->greaterThanOrEqualTo($automationCampaign->start_at);
    }

    private function shouldRunHourlyCampaign(AutomationCampaign $automationCampaign): bool
    {
        if ($this->isFirstCycle($automationCampaign) && $automationCampaign->start_at->isPast()) {
            return true;
        }

        $nextScheduledRun = $automationCampaign->last_sent_at->addHours($automationCampaign->hourly);

        return $nextScheduledRun->isPast();
    }

    private function shouldRunDailyCampaign(AutomationCampaign $automationCampaign): bool
    {
        if ($this->isFirstCycle($automationCampaign) && $automationCampaign->start_at->isPast()) {
            return true;
        }

        $nextScheduledRun = $automationCampaign->last_sent_at->addDay();

        return $nextScheduledRun->isToday() && now()->format('H') === $automationCampaign->start_at->format('H');
    }

    private function shouldRunWeeklyCampaign(AutomationCampaign $automationCampaign): bool
    {
        $isScheduledDayOfWeek = now()->isDayOfWeek($automationCampaign->weekly);

        if ($this->isFirstCycle($automationCampaign) && $isScheduledDayOfWeek && $automationCampaign->start_at->isPast()) {
            return true;
        }

        return $isScheduledDayOfWeek && now()->format('H') === $automationCampaign->start_at->format('H');
    }

    private function shouldRunMonthlyCampaign(AutomationCampaign $automationCampaign): bool
    {
        if (
            $this->isFirstCycle($automationCampaign)
            && now()->isSameDay($automationCampaign->start_at)
            && $automationCampaign->start_at->isPast()
        ) {
            return true;
        }

        $nextScheduledRun = $automationCampaign->last_sent_at->addMonth();

        return $nextScheduledRun->lte(now()) && now()->format('H') === $automationCampaign->start_at->format('H');
    }

    private function isFirstCycle(AutomationCampaign $automationCampaign): bool
    {
        return $automationCampaign->last_sent_at === null;
    }
}
