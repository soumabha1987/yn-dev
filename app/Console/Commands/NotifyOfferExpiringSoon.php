<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CommunicationCode;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\Consumer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class NotifyOfferExpiringSoon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'offer:expiring-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an offer expiration reminder 3 days and 1 day before expiry.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Consumer::query()
            ->select('id')
            ->where('offer_accepted', true)
            ->where('payment_setup', false)
            ->withWhereHas('consumerNegotiation', function (HasOne|Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('offer_accepted', true)
                        ->where(function (Builder $query) {
                            $query->where('first_pay_date', today()->addDays(3))
                                ->orWhere('first_pay_date', today()->addDay());
                        });
                })->orWhere(function (Builder $query): void {
                    $query->where('counter_offer_accepted', true)
                        ->where(function (Builder $query) {
                            $query->where('counter_first_pay_date', today()->addDays(3))
                                ->orWhere('counter_first_pay_date', today()->addDay());
                        });
                });
            })
            ->chunk(500, function (Collection $chunkConsumers): void {
                $chunkConsumers->each(function (Consumer $consumer): void {
                    $consumerNegotiation = $consumer->consumerNegotiation;

                    $offerAccepted = $consumerNegotiation->offer_accepted;
                    $counterOfferAccepted = $consumerNegotiation->counter_offer_accepted;

                    $communicationCode = match (true) {
                        $offerAccepted && $consumerNegotiation->first_pay_date->eq(today()->addDays(3)) => CommunicationCode::THREE_DAY_EXPIRATION_DATE_REMINDER,
                        $offerAccepted && $consumerNegotiation->first_pay_date->eq(today()->addDay()) => CommunicationCode::ONE_DAY_EXPIRATION_DATE_REMINDER,
                        $counterOfferAccepted && $consumerNegotiation->counter_first_pay_date->eq(today()->addDays(3)) => CommunicationCode::THREE_DAY_EXPIRATION_DATE_REMINDER,
                        $counterOfferAccepted && $consumerNegotiation->counter_first_pay_date->eq(today()->addDay()) => CommunicationCode::ONE_DAY_EXPIRATION_DATE_REMINDER,
                        default => null,
                    };

                    TriggerEmailAndSmsServiceJob::dispatchIf($communicationCode !== null, $consumer, $communicationCode);
                });
            });
    }
}
