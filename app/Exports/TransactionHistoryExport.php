<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionHistoryExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $transactions
    ) {}

    public function collection(): Collection
    {
        return $this->transactions->map(fn (Transaction $transaction): array => [
            'transaction_id' => $transaction->transaction_id,
            'transaction_type' => $transaction->transaction_type?->displayName() ?? '',
            'date' => $transaction->created_at->formatWithTimezone(format: 'M d, Y h:i A'),
            'status' => $transaction->status->displayName(),
            'amount' => Number::currency((float) ($transaction->amount ?? 0)),
            'customer_name' => $transaction->consumer->first_name . ' ' . $transaction->consumer->last_name,
            'account_number' => $transaction->consumer->account_number,
            'customer_status' => $transaction->consumer->status->displayLabel(),
            'payment_profile_id' => $transaction->paymentProfile->profile_id ?? '',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Transaction ID'),
            __('Transaction Type'),
            __('Date/Time'),
            __('Status'),
            __('Amount'),
            __('Customer Name'),
            __('Account Number'),
            __('Customer status'),
            __('Payment Profile number'),
        ];
    }
}
