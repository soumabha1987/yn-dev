<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Models\Consumer;
use App\Models\PaymentProfile;

class PaymentProfileService
{
    public function findByConsumer(int $consumerId): ?PaymentProfile
    {
        return PaymentProfile::query()
            ->where('consumer_id', $consumerId)
            ->latest()
            ->first();
    }

    public function deleteByConsumer(Consumer $consumer): void
    {
        PaymentProfile::query()
            ->where('consumer_id', $consumer->id)
            ->where('company_id', $consumer->company_id)
            ->where('subclient_id', $consumer->subclient_id)
            ->delete();
    }
}
