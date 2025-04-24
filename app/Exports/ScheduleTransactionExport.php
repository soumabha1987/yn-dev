<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\ScheduleTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ScheduleTransactionExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $scheduleTransactions
    ) {}

    public function collection(): Collection
    {
        return $this->scheduleTransactions->map(fn (ScheduleTransaction $scheduleTransaction): array => [
            'account_number' => $scheduleTransaction->consumer->account_number,
            'customer_name' => $scheduleTransaction->consumer->first_name . ' ' . $scheduleTransaction->consumer->last_name,
            'date' => $scheduleTransaction->schedule_date->formatWithTimezone(format: 'M d, Y h:i A'),
            'status' => $scheduleTransaction->status->displayName(),
            'amount' => Number::currency((float) ($scheduleTransaction->amount ?? 0)),
            'payment_method' => $scheduleTransaction->paymentProfile?->method->displayName(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Account Number'),
            __('Customer Name'),
            __('Date/Time'),
            __('Status'),
            __('Amount'),
            __('Payment Method'),
        ];
    }
}
