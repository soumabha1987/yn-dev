<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Communications\Group;

use App\Enums\ReportType;
use App\Enums\Role;
use App\Exports\ManageConsumersExport;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\Group;
use App\Models\User;
use App\Services\ConsumerService;
use App\Services\GroupService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListView extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    public ?int $groupSize = null;

    public string $totalBalance = '';

    public bool $openModal = false;

    protected ConsumerService $consumerService;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->consumerService = app(ConsumerService::class);

        $this->withUrl = true;
        $this->sortCol = 'created-on';
        $this->sortAsc = false;
    }

    public function export(Group $group): ?StreamedResponse
    {
        $consumers = $this->consumerService->fetchByGroup($group, $this->user->hasRole(Role::CREDITOR) ? $this->user->company_id : null);

        if ($consumers->isEmpty()) {
            $this->error(__('No consumers found in the selected group.'));

            return null;
        }

        $downloadFilename = $this->user->id . '_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv';

        $filename = 'download-report/' . Str::slug(ReportType::CONSUMERS->value) . '/' . $downloadFilename;

        Excel::store(
            new ManageConsumersExport($consumers, $this->user),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->dispatch('close-menu');

        return Storage::download($filename);
    }

    public function calculateGroupSize(Group $group): void
    {
        $consumer = $this->consumerService->countByGroup($group, $this->user->hasRole(Role::CREDITOR) ? $this->user->company_id : null);

        $this->groupSize = $consumer->getAttribute('total_count');
        $this->totalBalance = Number::currency((float) $consumer->total_balance);

        $this->openModal = true;

        $this->dispatch('close-menu');
    }

    public function delete(Group $group): void
    {
        if ($group->company_id !== $this->user->company_id && $this->user->id !== $group->user_id) {
            $this->error(__('You do not have permission to delete this group.'));

            return;
        }

        $group->delete();

        $this->dispatch('close-confirmation-box');

        $this->success(__('Successfully deleted.'));
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'created-on' => 'created_at',
            'name' => 'name',
            'consumer-status' => 'consumer_state',
            'pay-terms' => 'pay_terms',
            default => 'created_at',
        };

        $data = [
            'per_page' => $this->perPage,
            'company_id' => $this->user->company_id,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
            'user' => $this->user,
        ];

        return view('livewire.creditor.communications.group.list-view')
            ->with('groups', app(GroupService::class)->fetch($data));
    }
}
