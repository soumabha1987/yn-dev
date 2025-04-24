<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RecentTransactionsExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $transactions,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->transactions->map(fn (Transaction $transaction): array => [
            'date' => $transaction->created_at->formatWithTimezone(format: 'M d, Y h:i A'),
            'customer_name' => $transaction->consumer->first_name . ' ' . $transaction->consumer->last_name,
            'master_account_number' => $transaction->consumer->member_account_number,
            'type' => $transaction->transaction_type->displayOfferBadge(),
            'amount' => Number::currency((float) $transaction->amount),
            'subclien_name' => $transaction->consumer->subclient_name,
            'placement_date' => $transaction->consumer->placement_date?->format('M d, Y'),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Date'),
            __('Customer Name'),
            __('Account #'),
            __('Type'),
            __('Amount'),
            __('Sub Account Name'),
            __('Placement Date'),
        ];
    }
}
