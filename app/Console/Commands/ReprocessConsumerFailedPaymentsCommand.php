<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ConsumerStatus;
use App\Enums\MerchantName;
use App\Enums\TransactionStatus;
use App\Jobs\AuthorizeSchedulePaymentJob;
use App\Jobs\StripeSchedulePaymentJob;
use App\Jobs\TilledSchedulePaymentJob;
use App\Jobs\USAEpaySchedulePaymentJob;
use App\Models\ScheduleTransaction;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReprocessConsumerFailedPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reprocess:consumer-failed-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess failed transaction after 24 hours of actual scheduled date';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        ScheduleTransaction::query()
            ->withWhereHas('consumer', function (BelongsTo|Builder $query) {
                // We assume that if a payment profile is created, the merchant is already verified.
                $query->withWhereHas('paymentProfile.merchant')
                    ->with(['unsubscribe', 'consumerNegotiation'])
                    ->where('status', ConsumerStatus::PAYMENT_ACCEPTED->value)
                    ->where('offer_accepted', true);
            })
            ->whereNotNull('last_attempted_at')
            ->where('last_attempted_at', '<', Carbon::yesterday()->toDateTimeString())
            ->where('status', TransactionStatus::FAILED->value)
            ->where('attempt_count', 1)
            ->where('schedule_date', '<=', today()->toDateString())
            ->whereNotNull('payment_profile_id')
            ->each(function (ScheduleTransaction $scheduleTransaction): void {
                $merchantName = $scheduleTransaction->consumer->paymentProfile->merchant->merchant_name;

                $scheduleTransaction->update([
                    'attempt_count' => DB::raw('attempt_count + 1'),
                    'last_attempted_at' => now(),
                ]);

                try {
                    match ($merchantName) {
                        MerchantName::AUTHORIZE => AuthorizeSchedulePaymentJob::dispatch($scheduleTransaction),
                        MerchantName::USA_EPAY => USAEpaySchedulePaymentJob::dispatch($scheduleTransaction),
                        MerchantName::STRIPE => StripeSchedulePaymentJob::dispatch($scheduleTransaction),
                        MerchantName::YOU_NEGOTIATE => TilledSchedulePaymentJob::dispatch($scheduleTransaction),
                    };
                } catch (Exception $exception) {
                    Log::channel('daily')->error('There are error in pay installment of ' . $merchantName->value, [
                        'consumer' => $scheduleTransaction->consumer,
                        'payment_profile' => $scheduleTransaction->consumer->paymentProfile,
                        'message' => $exception->getMessage(),
                        'stack trace' => $exception->getTrace(),
                    ]);
                }
            });
    }
}
