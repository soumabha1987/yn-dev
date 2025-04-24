<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use App\Services\Consumer\ConsumerNegotiationService;
use App\Services\Consumer\PaymentProfileService;
use App\Services\Consumer\ScheduleTransactionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ResetExpiredOffersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restart:consumer-offer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart offers for expired consumer offers';

    /**
     * Execute the console command.
     */
    public function handle(
        ConsumerNegotiationService $consumerNegotiationService,
        ScheduleTransactionService $scheduleTransactionService,
        PaymentProfileService $paymentProfileService,
    ): void {
        Consumer::query()
            ->where('offer_accepted', true)
            ->where('payment_setup', false)
            ->withWhereHas('consumerNegotiation', function (HasOne|Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('offer_accepted', true)
                        ->where('first_pay_date', '<', today());
                })->orWhere(function (Builder $query): void {
                    $query->where('counter_offer_accepted', true)
                        ->where('counter_first_pay_date', '<', today());
                });
            })
            ->each(function (Consumer $consumer) use ($consumerNegotiationService, $scheduleTransactionService, $paymentProfileService): void {
                $consumer->update([
                    'status' => ConsumerStatus::JOINED,
                    'counter_offer' => false,
                    'offer_accepted' => false,
                    'payment_setup' => false,
                    'has_failed_payment' => false,
                    'custom_offer' => false,
                ]);

                $consumerNegotiationService->deleteByConsumer($consumer->id);
                $scheduleTransactionService->deleteScheduled($consumer->id);
                $paymentProfileService->deleteByConsumer($consumer);
            });
    }
}
