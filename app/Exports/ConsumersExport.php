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

class ConsumersExport implements FromCollection, WithHeadings
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
            $consumer->loadMissing(['company', 'subclient']);

            $data = collect([
                'account_number' => $consumer->member_account_number,
                'first_name' => $consumer->first_name ?? '',
                'last_name' => $consumer->last_name,
                'dob' => $consumer->dob->toDateString(),
                'ssn' => $consumer->last4ssn,
                'email' => $consumer->email1 ?? '',
                'mobile' => $consumer->mobile1 ?? '',
                'current_balance' => (float) $consumer->current_balance,
                'invitation_link' => $consumer->invitation_link,
                'status' => $consumer->status->displayLabel(),
                'reason' => $consumer->status === ConsumerStatus::NOT_PAYING ? $consumer->reason->label : '',
            ]);

            if ($this->user->hasRole(Role::SUPERADMIN)) {
                $data->putAfter([
                    'company_name' => $consumer->company->company_name ?? '',
                ], 'mobile');
            }

            if ($this->user->hasAnyRole(Role::CREDITOR, Role::SUPERADMIN)) {
                $data->putAfter([
                    'subclient_name' => $consumer->subclient->subclient_name ?? '',
                ], 'last_name');
            }

            return $data->all();
        });
    }

    public function headings(): array
    {
        $headings = collect([
            __('Member Account Number'),
            __('First Name'),
            __('Last Name'),
            __('Date Of Birth'),
            __('Last Four SSN'),
            __('Email'),
            __('Phone'),
            __('Current Balance'),
            __('Invitation Link'),
            __('Status'),
            __('Reason'),
        ]);

        if ($this->user->hasRole(Role::SUPERADMIN)) {
            $headings = $headings->putAfter([__('Company Name')], __('Phone'), true);
        }

        if ($this->user->hasAnyRole(Role::CREDITOR, Role::SUPERADMIN)) {
            $headings = $headings->putAfter([__('Sub Account Name')], __('Last Name'), true);
        }

        return $headings->all();
    }
}
