<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompaniesForPartnerExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private readonly Collection $companies,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->companies->map(fn (Company $company): array => [
            'partner_name' => $company->partner->name,
            'company_name' => $company->company_name ?? $company->owner_full_name,
            'created_at' => $company->created_at->formatWithTimezone(),
            'plan_name' => $company->activeCompanyMembership->membership->name ?? '',
            'yn_total_amount' => Number::currency(
                $company->getAttribute('total_yn_transactions_amount')
                + $company->getAttribute('total_membership_transactions_amount')
            ),
            'partner_revenue_amount' => Number::currency(
                $company->getAttribute('total_yn_transaction_partner_revenue')
                + $company->getAttribute('total_membership_transactions_partner_revenue')
            ),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Partner Name'),
            __('Member Name'),
            __('Date of Joined'),
            __('Membership Plan Name'),
            __('YN Total Rev.To Date'),
            __('Partner Rev.To Date'),
        ];
    }
}
