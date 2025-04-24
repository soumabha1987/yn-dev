<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentProfile;

class PaymentProfileService
{
    public function deleteByConsumer(int $consumerId): void
    {
        PaymentProfile::query()->where('consumer_id', $consumerId)->delete();
    }
}
