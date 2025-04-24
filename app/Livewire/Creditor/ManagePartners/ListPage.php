<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManagePartners;

use App\Exports\CompaniesForPartnerExport;
use App\Exports\PartnersExport;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\Partner;
use App\Services\CompanyService;
use App\Services\PartnerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    protected PartnerService $partnerService;

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'company-name';
        $this->partnerService = app(PartnerService::class);
    }

    public function export(): BinaryFileResponse
    {
        $partners = $this->partnerService->exportReports($this->setUp());

        return Excel::download(
            new PartnersExport($partners),
            now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv',
            writerType: ExcelExcel::CSV
        );
    }

    public function exportMembers(Partner $partner): ?BinaryFileResponse
    {
        $companies = app(CompanyService::class)->fetchForPartner($partner->id);

        if ($companies->isEmpty()) {
            $this->error('Sorry, this partner currently has no members.');

            $this->dispatch('close-menu-item');

            return null;
        }

        return Excel::download(
            new CompaniesForPartnerExport($companies),
            now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv',
            writerType: ExcelExcel::CSV
        );
    }

    private function setUp(): array
    {
        $column = match ($this->sortCol) {
            'company-name' => 'name',
            'contact-first-name' => 'contact_first_name',
            'contact-last-name' => 'contact_last_name',
            'contact-email' => 'contact_email',
            'contact-phone' => 'contact_phone',
            'revenue-share' => 'revenue_share',
            'creditors-quota' => 'creditors_quota',
            'joined' => 'companies_count',
            'quota-percentage' => 'quota_percentage',
            'yn-total-revenue' => 'yn_total_revenue',
            'partner-total-revenue' => 'partner_total_revenue',
            'yn-net-revenue' => 'yn_net_revenue',
            default => 'name',
        };

        return [
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];
    }

    public function render(): View
    {
        return view('livewire.creditor.manage-partners.list-page')
            ->with('partners', $this->partnerService->fetch([...$this->setUp(), 'per_page' => $this->perPage]))
            ->title(__('Manage Partners'));
    }
}
