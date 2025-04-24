<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageConsumers;

use App\Enums\ConsumerStatus;
use App\Enums\ReportType;
use App\Enums\Role;
use App\Exports\ManageConsumersExport;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\ConsumerService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * TODO: Make common functionality for sending an email, sms and delete to the consumers.
 */
class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    public bool $isSuperAdmin = false;

    public string $filterBySubscribes = 'all';

    public string $status = '';

    #[Url]
    public string $company = '';

    #[Url]
    public string $subclient = '';

    protected ConsumerService $consumerService;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->isSuperAdmin = $this->user->hasRole(Role::SUPERADMIN);
        if ($this->isSuperAdmin) {
            $this->withUrl = true;
        }
        $this->sortCol = 'master_account_number';
        $this->consumerService = app(ConsumerService::class);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function updatedCompany(): void
    {
        $this->reset('subclient');
    }

    public function export(): StreamedResponse
    {
        $consumers = $this->consumerService->getUsingFilters($this->setUp());

        $downloadFilename = $this->user->id . '_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv';

        $filename = 'download-report/' . Str::slug(ReportType::CONSUMERS->value) . '/' . $downloadFilename;

        Excel::store(
            new ManageConsumersExport($consumers, $this->user),
            $filename,
            writerType: ExcelExcel::CSV
        );

        return Storage::download($filename);
    }

    public function setUp(): array
    {
        $column = match ($this->sortCol) {
            'master_account_number' => 'member_account_number',
            'company_name' => 'company_name',
            'name' => 'name',
            'account_name' => 'original_account_name',
            'sub_name' => 'subclient_name',
            'placement_date' => 'placement_date',
            'account_status' => 'account_status',
            'status' => 'status',
            default => ''
        };

        return [
            'is_super_admin' => $this->isSuperAdmin,
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->subclient ?: $this->user->subclient_id,
            'column' => $column,
            'search' => $this->search,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
            'status' => $this->status,
            'company' => $this->company,
        ];
    }

    private function selectionBoxStatuses(): array
    {
        return [
            ConsumerStatus::UPLOADED->value => __('Offer Delivered'),
            ConsumerStatus::JOINED->value => __('Offer Viewed'),
            ConsumerStatus::PAYMENT_SETUP->value => __('In Negotiations'),
            'agreed_settlement_pending_payment' => __('Agreed Settlement/Pending Payment'),
            'agreed_payment_plan_pending_payment' => __('Agreed Payment Plan/Pending Payment'),
            'active_payment_plan' => __('Active Payment Plan'),
            ConsumerStatus::SETTLED->value => __('Settled/Paid'),
            ConsumerStatus::DISPUTE->value => __('Disputed'),
            ConsumerStatus::NOT_PAYING->value => __('Reported Not Paying'),
            ConsumerStatus::PAYMENT_DECLINED->value => __('Negotiations Closed'),
            ConsumerStatus::DEACTIVATED->value => __('Deactivated'),
            ConsumerStatus::HOLD->value => __('Account in Hold'),
        ];
    }

    public function render(): View
    {
        $subclients = [];

        if ($this->isSuperAdmin && $this->company) {
            $subclients = app(SubclientService::class)->fetchForSelectionBox((int) $this->company);
        }

        if ($this->user->hasRole(Role::CREDITOR)) {
            $subclients = app(SubclientService::class)->fetchForSelectionBox($this->user->company_id);
        }

        $this->search = Session::get('search', '');

        return view('livewire.creditor.manage-consumers.list-page')
            ->with([
                'consumers' => $this->consumerService->fetchUsingFilters([...$this->setUp(), 'per_page' => $this->perPage]),
                'companies' => $this->isSuperAdmin ? app(CompanyService::class)->fetchForSelectionBox() : [],
                'subclients' => $subclients,
                'statuses' => $this->selectionBoxStatuses(),
            ])
            ->title(__('Consumer Profile(s)'));
    }
}
