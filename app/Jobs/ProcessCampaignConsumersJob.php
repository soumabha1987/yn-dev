<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Services\ConsumerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProcessCampaignConsumersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Campaign $campaign,
        protected CampaignTracker $campaignTracker,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $companyId = $this->campaign->company ? $this->campaign->company_id : null;

        app(ConsumerService::class)
            ->fetchByGroupBuilder($this->campaign->group, $companyId)
            ->chunk(500, function (Collection $chunkConsumers): void {
                $this->campaignTracker->consumers()->attach($chunkConsumers);

                SendCampaignEmailSmsOrELetterJob::dispatch($chunkConsumers, $this->campaign, $this->campaignTracker);
            });
    }
}
