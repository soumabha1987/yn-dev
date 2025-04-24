<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Traits\MyAccounts;

use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use App\Services\Consumer\ConsumerNegotiationService;
use App\Services\Consumer\PaymentProfileService;
use App\Services\Consumer\ScheduleTransactionService;

trait StartOver
{
    public function startOver(Consumer $consumer): void
    {
        $this->resetNegotiation($consumer);

        $this->redirectRoute('consumer.negotiate', ['consumer' => $consumer->id], navigate: true);
    }

    public function restartNegotiation(Consumer $consumer): void
    {
        $this->resetNegotiation($consumer);

        $this->dispatch('close-confirmation-box');
    }

    private function resetNegotiation(Consumer $consumer): void
    {
        $consumer->update([
            'status' => ConsumerStatus::JOINED,
            'counter_offer' => false,
            'offer_accepted' => false,
            'payment_setup' => false,
            'has_failed_payment' => false,
            'custom_offer' => false,
        ]);

        app(ConsumerNegotiationService::class)->deleteByConsumer($consumer->id);
        app(ScheduleTransactionService::class)->deleteScheduled($consumer->id);
        app(PaymentProfileService::class)->deleteByConsumer($consumer);
    }
}
