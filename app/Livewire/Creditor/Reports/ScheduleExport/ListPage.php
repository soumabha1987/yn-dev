<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Reports\ScheduleExport;

use App\Enums\FeatureName;
use App\Enums\Role;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\ScheduleExport;
use App\Models\User;
use App\Services\FeatureFlagService;
use App\Services\ScheduleExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();

        $this->withUrl = true;
        $this->sortCol = 'created_on';
        $this->sortAsc = false;
    }

    public function delete(ScheduleExport $scheduleExport): void
    {
        $scheduleExport->delete();

        $this->success(__('Schedule report deleted successfully.'));

        $this->dispatch('close-confirmation-box');
    }

    public function togglePause(ScheduleExport $scheduleExport): void
    {
        $scheduleExport->update(['pause' => ! $scheduleExport->pause]);

        $this->success(__('Schedule export :action successfully', ['action' => $scheduleExport->pause ? 'Paused' : 'Resumed']));
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'created_on' => 'created_at',
            'type' => 'report_type',
            'frequency' => 'frequency',
            'client_name' => 'company_name',
            'delivery_type' => 'sftp_connection_id',
            default => 'created_at'
        };

        $data = [
            'per_page' => $this->perPage,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
            'user_id' => $this->user->id,
            'company_id' => $this->user->hasRole(Role::CREDITOR) ? $this->user->company_id : null,
            'is_super_admin' => $this->user->hasRole(Role::SUPERADMIN),
        ];

        return view('livewire.creditor.reports.schedule-export.list-page')
            ->with([
                'scheduleExports' => app(ScheduleExportService::class)->fetch($data),
                'thisFeatureIsDisabled' => app(FeatureFlagService::class)->disabled(FeatureName::SCHEDULE_EXPORT),
            ])
            ->title(__('Schedule a Report'));
    }
}
