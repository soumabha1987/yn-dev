<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Consumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DisputeNoPayExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $consumers,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->consumers->map(fn (Consumer $consumer): array => [
            'yn_status' => $consumer->reason?->label,
            'disputed_at' => $consumer->disputed_at->formatWithTimezone(),
            'first_name' => $consumer->first_name,
            'last_name' => $consumer->last_name,
            'date_of_birth' => $consumer->dob->format('M d, Y'),
            'last4ssn' => $consumer->last4ssn,
            'account_balance' => Number::currency((float) $consumer->current_balance),
            'account_name' => $consumer->original_account_name,
            'original_account_number' => $consumer->account_number,
            'member_account_number' => $consumer->member_account_number,
            'reference_number' => $consumer->reference_number,
            'statement_number' => $consumer->statement_id_number,
            'subclient_id' => $consumer->subclient_id,
            'subclient_name' => $consumer->subclient_name,
            'subclient_number' => $consumer->subclient_account_number,
            'placement_date' => $consumer->placement_date?->format('M d, Y'),
            'expiry_date' => $consumer->expiry_date?->format('M d, Y'),
        ]);
    }

    public function headings(): array
    {
        return [
            __('YN Status'),
            __('Date Reported By Consumer'),
            __('First Name'),
            __('Last Name'),
            __('Date Of Birth'),
            __('Last 4 Ssn'),
            __('Account Balance'),
            __('Account Name'),
            __('Original Account Number'),
            __('Member Account Number'),
            __('Reference Number'),
            __('Statement Number'),
            __('Sub Identification (ID)'),
            __('Subclient Name'),
            __('Subclient Number'),
            __('Placement Date'),
            __('Expiry Date'),
        ];
    }
}
