<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Company;
use App\Models\Subclient;
use App\Services\CompanyService;
use App\Services\SubclientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class MerchantWebhookCreationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected ?string $tilledMerchantAccountId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        CompanyService $companyService,
        SubclientService $subclientService
    ): void {
        if ($this->tilledMerchantAccountId === null) {
            return;
        }

        $company = $companyService->fetchByTilledMerchantForWebhookCreation($this->tilledMerchantAccountId);

        if ($company) {
            $this->createForCompany($company);

            return;
        }

        $subclient = $subclientService->fetchByTilledMerchantForWebhookCreation($this->tilledMerchantAccountId);

        if ($subclient) {
            $this->createForSubclient($subclient);
        }
    }

    private function createForCompany(Company $company): void
    {
        $response = Http::tilled($company->tilled_merchant_account_id)
            ->post('webhook-endpoints', [
                'enabled_events' => ['payment_intent.payment_failed', 'payment_intent.canceled'],
                'description' => 'This webhook is created under the main account to listen for consumer payments.',
                'url' => route('tilled-webhook-listener'),
            ]);

        if ($response->created()) {
            $company->update([
                'tilled_webhook_secret' => $response->json('secret'),
            ]);
        }
    }

    private function createForSubclient(Subclient $subclient)
    {
        $response = Http::tilled($subclient->tilled_merchant_account_id)
            ->post('webhook-endpoints', [
                'enabled_events' => ['payment_intent.payment_failed', 'payment_intent.canceled'],
                'description' => 'This webhook is created under the main account to listen for consumer payments.',
                'url' => route('tilled-webhook-listener'),
            ]);

        if ($response->created()) {
            $subclient->update([
                'tilled_webhook_secret' => $response->json('secret'),
            ]);
        }
    }
}
