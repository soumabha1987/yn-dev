<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Models\ConsumerNegotiation;

class ConsumerNegotiationService
{
    public function deleteByConsumer(int $consumerId): void
    {
        ConsumerNegotiation::query()
            ->where('consumer_id', $consumerId)
            ->delete();
    }

    public function fetchActive(int $consumerId): ?ConsumerNegotiation
    {
        return ConsumerNegotiation::query()
            ->where('active_negotiation', true)
            ->where('offer_accepted', false)
            ->where('counter_offer_accepted', false)
            ->where('consumer_id', $consumerId)
            ->first();
    }
}
