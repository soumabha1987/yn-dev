<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\TransactionType;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UpcomingTransactionsExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $transactions,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->transactions->map(function (ScheduleTransaction $scheduleTransaction): array {
            /** @var Carbon $scheduleDate */
            $scheduleDate = $scheduleTransaction->schedule_date;

            /** @var Consumer $consumer */
            $consumer = $scheduleTransaction->consumer;

            return [
                'schedule_date' => $scheduleDate->formatWithTimezone(),
                'schedule amount' => Number::currency((float) ($scheduleTransaction->amount ?? 0)),
                'pay_type' => $scheduleTransaction->transaction_type === TransactionType::PIF ? __('Settle') : __('Pay Plan'),
                'consumer_name' => $consumer->first_name . ' ' . $consumer->last_name,
                'master_account_number' => $consumer->member_account_number,
                'original_account_name' => $consumer->original_account_name,
                'subclient_name' => $consumer->subclient_name,
                'placement_date' => $consumer->placement_date?->format('M d, Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            __('Schedule date'),
            __('Schedule amount'),
            __('Pay Type'),
            __('Consumer name'),
            __('Account #'),
            __('Account name'),
            __('Sub Account Name'),
            __('Placement Date'),
        ];
    }
}
