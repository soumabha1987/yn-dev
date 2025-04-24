<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\MembershipTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CreditorBillingHistoriesExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $billingHistories
    ) {}

    public function collection(): Collection
    {
        return $this->billingHistories->map(fn (MembershipTransaction $billingHistory): array => [
            'plan_date' => $billingHistory->created_at->formatWithTimezone(),
            'invoice' => $billingHistory->getAttribute('invoice_id'),
            'amount' => Number::currency((float) ($billingHistory->amount ?? 0)),
            'method' => data_get($billingHistory, 'response.payment_method.card.last4')
                ? '*** *** *** ' . data_get($billingHistory, 'response.payment_method.card.last4')
                : '',
            'status' => $billingHistory->status->value,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Plan Date'),
            __('Invoice #'),
            __('Total Amount'),
            __('Pay Method'),
            __('Payment Status'),
        ];
    }
}
