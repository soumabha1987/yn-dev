<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\MembershipTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BillingHistoriesExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $membershipTransactions
    ) {}

    public function collection(): Collection
    {
        return $this->membershipTransactions->map(fn (MembershipTransaction $membershipTransaction): array => [
            'invoice_id' => $membershipTransaction->getAttribute('invoice_id'),
            'plan_name' => $membershipTransaction->membership?->name,
            'company_name' => $membershipTransaction->company->company_name,
            'date' => $membershipTransaction->created_at->formatWithTimezone(),
            'amount' => Number::currency((float) ($membershipTransaction->amount ?? 0)),
            'transaction_id' => data_get($membershipTransaction, 'response.id'),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Invoice Id'),
            __('Plan Name'),
            __('Company Name'),
            __('Date'),
            __('Amount'),
            __('Transaction ID'),
        ];
    }
}
