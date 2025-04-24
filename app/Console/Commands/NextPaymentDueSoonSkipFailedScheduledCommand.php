<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CommunicationCode;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Jobs\SkipScheduleTransactionJob;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\ScheduleTransaction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class NextPaymentDueSoonSkipFailedScheduledCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skip:consumer-failed-payments-nearing-next-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Skip scheduled consumer payments if 72 hours away from next due';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        ScheduleTransaction::query()
            ->with('consumer.unsubscribe')
            ->withWhereHas('consumer.consumerNegotiation', function (HasOne|Builder $query): void {
                $query->where('active_negotiation', true);
            })
            ->where('status', TransactionStatus::FAILED->value)
            ->where('attempt_count', '>', 1)
            ->where('schedule_date', '<=', now())
            ->where('transaction_type', TransactionType::INSTALLMENT->value)
            ->whereNotNull('last_attempted_at')
            ->whereExists(function (QueryBuilder $query): void {
                $query->select(DB::raw(1))
                    ->from('schedule_transactions as st')
                    ->whereColumn('st.consumer_id', 'schedule_transactions.consumer_id')
                    ->whereColumn('st.company_id', 'schedule_transactions.company_id')
                    ->where(function (QueryBuilder $query): void {
                        $query->whereColumn('st.subclient_id', 'schedule_transactions.subclient_id')
                            ->orWhere(function (QueryBuilder $query): void {
                                $query->whereNull('st.subclient_id')
                                    ->whereNull('schedule_transactions.subclient_id');
                            });
                    })
                    ->whereColumn('st.schedule_date', '>', 'schedule_transactions.schedule_date')
                    ->where('st.status', TransactionStatus::SCHEDULED->value)
                    ->where('st.transaction_type', TransactionType::INSTALLMENT->value)
                    ->where('st.schedule_date', '<=', now()->addHours(72)->toDateString())
                    ->orderBy('st.schedule_date');
            })
            ->each(function (ScheduleTransaction $potentialScheduleToBeSkipped) {
                SkipScheduleTransactionJob::dispatch($potentialScheduleToBeSkipped);

                $consumer = $potentialScheduleToBeSkipped->consumer;

                TriggerEmailAndSmsServiceJob::dispatch($consumer, CommunicationCode::PAYMENT_FAILED_MOVE_TO_SKIP);
            });
    }
}
