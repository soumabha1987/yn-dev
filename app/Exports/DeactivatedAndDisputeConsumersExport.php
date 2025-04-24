<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\Role;
use App\Models\Consumer;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DeactivatedAndDisputeConsumersExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $consumers,
        private User $user
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->consumers->map(function (Consumer $consumer): array {
            $consumer->loadMissing('subclient');

            $data = collect([
                'account_number' => $consumer->account_number,
                'consumer_name' => $consumer->first_name . ' ' . $consumer->last_name,
                'last_four_ssn' => $consumer->last4ssn,
                'email' => $consumer->email1,
                'mobile' => $consumer->mobile1,
                'status' => $consumer->status->displayLabel(),
            ]);

            if ($this->user->hasRole(Role::CREDITOR)) {
                $data->putAfter([
                    'subclient_name' => $consumer->subclient->subclient_name ?? '',
                ], 'consumer_name');
            }

            return $data->all();
        });
    }

    public function headings(): array
    {
        $headings = collect([
            __('Account number'),
            __('Consumer name'),
            __('Last four SSN'),
            __('Email'),
            __('Mobile Number'),
            __('Status'),
        ]);

        if ($this->user->hasRole(Role::CREDITOR)) {
            return $headings->putAfter([__('Sub account name')], __('Consumer name'), true)->all();
        }

        return $headings->all();
    }
}
