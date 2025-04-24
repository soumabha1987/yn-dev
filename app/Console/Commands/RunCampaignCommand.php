<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessCampaignConsumersJob;
use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Services\CampaignService;
use App\Services\ConsumerService;
use App\Services\EcoLetterPaymentService;
use Illuminate\Console\Command;

class RunCampaignCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run campaigns to consumer send.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        app(CampaignService::class)
            ->fetchTodayRun()
            ->each(function (Campaign $campaign): void {
                $companyId = $campaign->company ? $campaign->company_id : null;

                $groupConsumerCount = app(ConsumerService::class)
                    ->countByGroup($campaign->group, $companyId);

                $consumersTotalAmount = $groupConsumerCount->getAttribute('total_balance');

                $consumerCount = $groupConsumerCount->getAttribute('total_count');

                if ($consumerCount === 0) {
                    return;
                }

                if ($companyId && ! app(EcoLetterPaymentService::class)->applyEcoLetterDeduction($campaign->company, $consumerCount)) {
                    return;
                }

                $campaignTracker = CampaignTracker::query()->create([
                    'campaign_id' => $campaign->id,
                    'consumer_count' => $consumerCount,
                    'total_balance_of_consumers' => $consumersTotalAmount ?? 0,
                ]);

                ProcessCampaignConsumersJob::dispatch($campaign, $campaignTracker);
            });
    }
}
