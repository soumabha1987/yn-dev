<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\MerchantWebhookCreationJob;
use App\Models\Company;
use App\Models\Subclient;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MerchantWebhookCreationJobTest extends TestCase
{
    #[Test]
    public function it_can_create_webhook_endpoint_for_company(): void
    {
        Http::fake(fn () => Http::response([
            'secret' => fake()->uuid(),
        ], Response::HTTP_CREATED));

        $company = Company::factory()->create([
            'tilled_webhook_secret' => null,
        ]);

        MerchantWebhookCreationJob::dispatchSync($company->tilled_merchant_account_id);

        $this->assertNotNull($company->refresh()->tilled_webhook_secret);
    }

    #[Test]
    public function it_can_create_webhook_endpoint_for_subclient(): void
    {
        Http::fake(fn () => Http::response([
            'secret' => fake()->uuid(),
        ], Response::HTTP_CREATED));

        $subclient = Subclient::factory()->create([
            'tilled_webhook_secret' => null,
        ]);

        MerchantWebhookCreationJob::dispatchSync($subclient->tilled_merchant_account_id);

        $this->assertNotNull($subclient->refresh()->tilled_webhook_secret);
    }
}
