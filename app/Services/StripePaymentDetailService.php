<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StripePaymentDetail;

class StripePaymentDetailService
{
    public function deleteByConsumer(int $consumerId): void
    {
        StripePaymentDetail::query()->where('consumer_id', $consumerId)->delete();
    }
}
