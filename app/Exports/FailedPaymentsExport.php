<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\ScheduleTransaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FailedPaymentsExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $scheduleTransactions,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->scheduleTransactions->map(fn (ScheduleTransaction $scheduleTransaction): array => [
            'due_date' => $scheduleTransaction->schedule_date->formatWithTimezone(),
            'last_failed_date' => $scheduleTransaction->last_attempted_at->formatWithTimezone(),
            'master_account_number' => $scheduleTransaction->consumer->member_account_number,
            'consumer_name' => $scheduleTransaction->consumer->first_name . ' ' . $scheduleTransaction->consumer->last_name,
            'account_name' => $scheduleTransaction->consumer->original_account_name,
            'sub_account_name' => $scheduleTransaction->consumer->subclient_name,
            'placement_date' => $scheduleTransaction->consumer->placement_date?->format('M d, y'),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Due Date'),
            __('Last Failed Date'),
            __('Account #'),
            __('Consumer Name'),
            __('Account Name'),
            __('Sub Account Name'),
            __('Placement Date'),
        ];
    }
}
