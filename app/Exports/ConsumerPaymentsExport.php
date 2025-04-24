<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsumerPaymentsExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $transactions
    ) {}

    public function collection(): Collection
    {
        return $this->transactions->map(fn (Transaction $transaction): array => [
            'first_name' => $transaction->consumer->first_name,
            'last_name' => $transaction->consumer->last_name,
            'date_of_birth' => $transaction->consumer->dob->format('M d, Y'),
            'last4ssn' => $transaction->consumer->last4ssn,
            'account_name' => $transaction->consumer->original_account_name,
            'account_number' => $transaction->consumer->account_number,
            'member_account_number' => $transaction->consumer->member_account_number,
            'reference_number' => $transaction->consumer->reference_number,
            'statement_number' => $transaction->consumer->statement_id_number,
            'subclient_id' => $transaction->consumer->subclient_id,
            'subclient_name' => $transaction->consumer->subclient_name,
            'subclient_number' => $transaction->consumer->subclient_account_number,
            'placement_date' => $transaction->consumer->placement_date?->format('M d, Y'),
            'expiry_date' => $transaction->consumer->expiry_date?->format('M d, Y'),
            'payment_type' => $transaction->transaction_type->displayOfferBadge(),
            'payment_date' => $transaction->created_at->formatWithTimezone(),
            'payment_amount' => Number::currency((float) $transaction->amount),
            'processing_fees_deducted' => Number::currency((float) $transaction->rnn_share),
            'net_payment_amount' => Number::currency((float) $transaction->company_share),
        ]);
    }

    public function headings(): array
    {
        return [
            __('First Name'),
            __('Last Name'),
            __('Date Of Birth'),
            __('Last 4 Ssn'),
            __('Account Name'),
            __('Original Account Number'),
            __('Member Account Number'),
            __('Reference Number'),
            __('Statement Number'),
            __('Sub Identification (ID)'),
            __('Subclient Name'),
            __('Subclient Account Number'),
            __('Placement Date'),
            __('Expiry Date'),
            __('Payment Type'),
            __('Payment Date'),
            __('Payment Amount'),
            __('Processing Fees Deducted'),
            __('Net Payment Amount'),
        ];
    }
}
