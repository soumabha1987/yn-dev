<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ConsumerStatus;
use App\Enums\MerchantName;
use App\Enums\TransactionStatus;
use App\Jobs\AuthorizeSchedulePaymentJob;
use App\Jobs\SkipScheduleTransactionJob;
use App\Jobs\StripeSchedulePaymentJob;
use App\Jobs\TilledSchedulePaymentJob;
use App\Jobs\USAEpaySchedulePaymentJob;
use App\Models\ScheduleTransaction;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessConsumerPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:consumer-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send request to payment gateway to deduct consumer payment';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        /**
         * We are not going to drop the `payment_profile_id` from the `schedule_transactions` because the consumer may change at any time!
         * Therefore, we will retrieve the latest data from the consumer rather than from the schedule transactions.
         */
        ScheduleTransaction::query()
            ->withWhereHas('consumer', function (BelongsTo|Builder $query) {
                // We assume that if a payment profile is created, the merchant is already verified.
                $query->withWhereHas('paymentProfile.merchant')
                    ->whereHas('company')
                    ->with(['unsubscribe', 'consumerNegotiation'])
                    ->whereIn('status', [ConsumerStatus::PAYMENT_ACCEPTED, ConsumerStatus::HOLD])
                    ->where('offer_accepted', true);
            })
            ->where('schedule_date', '<=', now()->toDateString())
            ->where('attempt_count', 0)
            ->where('status', TransactionStatus::SCHEDULED->value)
            ->whereNotNull('payment_profile_id')
            ->each(function (ScheduleTransaction $scheduleTransaction): void {
                $merchantName = $scheduleTransaction->consumer->paymentProfile->merchant->merchant_name;

                if ($scheduleTransaction->consumer->status === ConsumerStatus::HOLD) {
                    SkipScheduleTransactionJob::dispatch($scheduleTransaction);

                    return;
                }

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
