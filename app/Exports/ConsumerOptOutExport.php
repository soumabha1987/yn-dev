<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Consumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsumerOptOutExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $consumers,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->consumers->map(function (Consumer $consumer): array {

            $textOptOut = ! $consumer->consumerProfile->text_permission;
            $emailOptOut = ! $consumer->consumerProfile->email_permission;

            $type = match (true) {
                $textOptOut && $emailOptOut => __('email and mobile opt out'),
                $textOptOut => __('mobile opt out'),
                $emailOptOut => __('email opt out'),
                default => __('email and mobile opt in'),
            };

            return [
                'type' => $type,
                'first_name' => $consumer->first_name,
                'last_name' => $consumer->last_name,
                'date_of_birth' => $consumer->dob->format('M d, Y'),
                'last4ssn' => $consumer->last4ssn,
                'account_balance' => Number::currency((float) $consumer->current_balance),
                'account_name' => $consumer->original_account_name,
                'account_number' => $consumer->account_number,
                'member_account_number' => $consumer->member_account_number,
                'reference_number' => $consumer->reference_number,
                'statement_number' => $consumer->statement_id_number,
                'subclient_id' => $consumer->subclient_id,
                'subclient_name' => $consumer->subclient_name,
                'subclient_number' => $consumer->subclient_account_number,
                'placement_date' => $consumer->placement_date?->format('M d, Y'),
                'expiry_date' => $consumer->expiry_date?->format('M d, Y'),
                'email' => $consumer->email1,
                'email_status' => $emailOptOut ? __('email opt out') : __('email opt in'),
                'phone_number' => $consumer->mobile1,
                'mobile_status' => $textOptOut ? __('mobile opt out') : __('mobile opt in'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            __('Type'),
            __('First Name'),
            __('Last Name'),
            __('Date Of Birth'),
            __('Last 4 SSN'),
            __('Account Balance'),
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
            __('Consumer Email'),
            __('Email Status'),
            __('Consumer Mobile Phone'),
            __('Mobile Status'),
        ];
    }
}
