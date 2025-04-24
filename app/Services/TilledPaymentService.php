<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\DeleteTilledCustomerJob;
use App\Models\Company;
use App\Models\MembershipPaymentProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TilledPaymentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createOrUpdateCustomer(Company $company, array $data): ?string
    {
        $tilledPaymentMethodId = data_get($data, 'tilled_response.id');

        if (! $tilledPaymentMethodId) {
            return null;
        }

        $company->loadMissing('membershipPaymentProfile');

        /** @var ?MembershipPaymentProfile $membershipPaymentProfile */
        $membershipPaymentProfile = $company->membershipPaymentProfile;

        $deleteCustomer = false;

        if (filled($membershipPaymentProfile?->tilled_customer_id) && filled($membershipPaymentProfile->tilled_payment_method_id)) {
            $detached = $this->detachPaymentMethod($membershipPaymentProfile->tilled_customer_id, $membershipPaymentProfile->tilled_payment_method_id);

            if (! $detached) {
                $deleteCustomer = $membershipPaymentProfile->tilled_customer_id;
            }
        }

        $customerData = [
            'first_name' => data_get($data, 'first_name') ?? $membershipPaymentProfile->first_name,
            'last_name' => data_get($data, 'last_name') ?? $membershipPaymentProfile->last_name,
            'email' => $company->owner_email,
            'phone' => $company->owner_phone,
        ];

        $method = 'patch';
        $url = "/customers/$membershipPaymentProfile?->tilled_customer_id";

        if (blank($membershipPaymentProfile?->tilled_customer_id) || $deleteCustomer) {
            $method = 'post';
            $url = '/customers';
        }

        $customerResponse = Http::tilled(config('services.merchant.tilled_merchant_account_id'))
            ->$method($url, $customerData)
            ->json();

        $customerId = data_get($customerResponse, 'id');

        if (! $customerId) {
            return null;
        }

        if (! $this->attachPaymentMethod($customerId, $tilledPaymentMethodId)) {
            return null;
        }

        DeleteTilledCustomerJob::dispatchIf($deleteCustomer, $deleteCustomer);

        return $customerId;
    }

    public function attachPaymentMethod(string $customerId, string $tilledPaymentMethodId): bool
    {
        $response = Http::tilled(config('services.merchant.tilled_merchant_account_id'))
            ->put("/payment-methods/$tilledPaymentMethodId/attach", [
                'customer_id' => $customerId,
            ]);

        if ($response->failed()) {
            Log::channel('daily')->error('Tilled attach customer failed', [
                'tilled_payment_method_id' => $tilledPaymentMethodId,
                'tilled_customer_id' => $customerId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return Arr::has($response, 'id');
    }

    public function detachPaymentMethod(string $customerId, string $oldPaymentMethodId): bool
    {
        $response = Http::tilled(config('services.merchant.tilled_merchant_account_id'))
            ->put("/payment-methods/$oldPaymentMethodId/detach", [
                'customer_id' => $customerId,
            ]);

        if ($response->failed()) {
            Log::channel('daily')->error('Tilled detach customer failed', [
                'old_payment_method_id' => $oldPaymentMethodId,
                'tilled_customer_id' => $customerId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return Arr::has($response, 'id');
    }

    public function createPaymentIntents(int $amount, ?string $paymentMethodId): array
    {
        if ($paymentMethodId === null) {
            return [];
        }

        return Http::tilled(config('services.merchant.tilled_merchant_account_id'))
            ->post('payment-intents', [
                'amount' => $amount,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'payment_method_id' => $paymentMethodId,
                'confirm' => true,
            ])
            ->json();
    }
}
