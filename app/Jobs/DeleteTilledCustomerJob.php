<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeleteTilledCustomerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $customerId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Http::tilled(config('services.merchant.tilled_merchant_account_id'))
            ->delete('customers/' . $this->customerId);

        if ($response->failed()) {
            Log::channel('daily')->error('Tilled delete customer', [
                'tilled_customer_id' => $this->customerId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
