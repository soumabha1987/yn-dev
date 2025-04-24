<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CFPBConsumersExport implements FromCollection, WithHeadings
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
            'account_number' => $consumer->member_account_number,
            'first_name' => $consumer->first_name,
            'last_name' => $consumer->last_name,
            'dob' => $consumer->dob->toDateString(),
            'ssn' => $consumer->last4ssn,
            'current_balance' => (float) $consumer->current_balance,
            'original_account_name' => $consumer->original_account_name,
            'subclient_name' => $consumer->subclient_name ? $consumer->subclient_name . '/' . $consumer->subclient_account_number : '',
            'placement_date' => $consumer->placement_date ? $consumer->placement_date->format('M d, Y') : '',
            'account_status' => in_array($consumer->status, [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]) ? __('Removed') : __('Active'),
            'status' => $consumer->status->displayLabel(),
            'reason' => $consumer->status === ConsumerStatus::NOT_PAYING ? $consumer->reason->label : '',
        ]);
    }

    public function headings(): array
    {
        return [
            __('Master Account Number'),
            __('First Name'),
            __('Last Name'),
            __('Date Of Birth'),
            __('Last Four SSN'),
            __('Current Balance'),
            __('Account Name'),
            __('Sub Name/ID'),
            __('Placement Date'),
            __('Account Status'),
            __('Negotiation Status'),
            __('Reason'),
        ];
    }
}
