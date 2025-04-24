<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\ConsumerStatus;
use App\Enums\Role;
use App\Models\Consumer;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ManageConsumersExport implements FromCollection, WithHeadings
{
    public function __construct(
        private Collection $consumers,
        private User $user,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->consumers->map(function (Consumer $consumer): array {
            $consumer->loadMissing('company');

            $data = collect([
                'account_number' => $consumer->member_account_number ?? '',
                'first_name' => $consumer->first_name ?? '',
                'last_name' => $consumer->last_name,
                'dob' => $consumer->dob->toDateString(),
                'ssn' => $consumer->last4ssn,
                'current_balance' => (float) $consumer->current_balance,
                'original_account_name' => $consumer->original_account_name,
                'subclient_name' => $consumer->subclient_name ? $consumer->subclient_name . '/' . $consumer->subclient_account_number : '',
                'placement_date' => $consumer->placement_date ? $consumer->placement_date->format('m d, Y') : '',
                'account_status' => in_array($consumer->status, [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]) ? __('Removed') : __('Active'),
                'status' => $consumer->status->displayLabel(),
                'reason' => $consumer->status === ConsumerStatus::NOT_PAYING ? $consumer->reason->label : '',
            ]);

            if ($this->user->hasRole(Role::SUPERADMIN)) {
                $data->putAfter([
                    'company_name' => $consumer->company->company_name ?? '',
                    'invitation_link' => $consumer->invitation_link,
                ], 'subclient_name');
            }

            return $data->all();
        });
    }

    public function headings(): array
    {
        $headings = collect([
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
        ]);

        if ($this->user->hasRole(Role::SUPERADMIN)) {
            $headings = $headings->putAfter([__('Company Name'), __('Invitation Link')], __('Sub Name/ID'), true);
        }

        return $headings->all();
    }
}
