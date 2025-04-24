<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Consumer;
use App\Models\ConsumerUnsubscribe;

class ConsumerUnsubscribeService
{
    public function create(Consumer $consumer): void
    {
        ConsumerUnsubscribe::query()
            ->create([
                'company_id' => $consumer->company_id,
                'consumer_id' => $consumer->id,
                'email' => $consumer->email1,
                'phone' => $consumer->mobile1,
            ]);
    }

    public function delete(Consumer $consumer): void
    {
        ConsumerUnsubscribe::query()
            ->where('company_id', $consumer->company_id)
            ->where('consumer_id', $consumer->id)
            ->delete();
    }

    public function deleteByConsumer(int $consumerId): void
    {
        ConsumerUnsubscribe::query()->where('consumer_id', $consumerId)->delete();
    }
}
