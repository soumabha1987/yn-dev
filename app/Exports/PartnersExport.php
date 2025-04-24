<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Partner;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PartnersExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private readonly Collection $partners,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->partners->map(function (Partner $partner): array {

            $totalAmount = $partner->getAttribute('total_yn_transactions_amount')
                + $partner->getAttribute('total_membership_transactions_amount');

            $partnerTotalAmount = $partner->getAttribute('total_yn_transaction_partner_revenue')
                + $partner->getAttribute('total_membership_transactions_partner_revenue');

            return [
                'company_name' => $partner->name,
                'contact_first_name' => $partner->contact_first_name,
                'contact_last_name' => $partner->contact_last_name,
                'contact_email' => $partner->contact_email,
                'contact_phone' => $partner->contact_phone,
                'report_emails' => $partner->report_emails,
                'revenue_share' => Number::percentage((float) ($partner->revenue_share ?? 0)),
                'creditors_quota' => Number::format($partner->creditors_quota ?? 0),
                'creditor_joined' => Number::format($partner->companies_count ?? 0),
                'quota_percentage' => Number::percentage($partner->companies_count > 0 ? (($partner->companies_count * 100) / $partner->creditors_quota) : 0, 2),
                'yn_total_amount' => Number::currency((float) $totalAmount),
                'partner_revenue_amount' => Number::currency((float) $partnerTotalAmount),
                'yn_net_amount' => Number::currency((float) $totalAmount - $partnerTotalAmount),
            ];
        });
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Company Name'),
            __('Contact First Name'),
            __('Contact Last Name'),
            __('Contact Email'),
            __('Contact Phone'),
            __('Report Email(s)'),
            __('Revenue Share %'),
            __('# Quota'),
            __('# Joined'),
            __('% Quota'),
            __('YN Total Rev.To Date'),
            __('Partner Rev.To Date'),
            __('YN Net Rev.To Date'),
        ];
    }
}
