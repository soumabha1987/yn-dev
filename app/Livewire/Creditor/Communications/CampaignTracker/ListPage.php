<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Communications\CampaignTracker;

use App\Enums\CampaignFrequency;
use App\Exports\ConsumersExport;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\CampaignTracker;
use App\Models\User;
use App\Services\CampaignTrackerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->withUrl = true;
        $this->sortCol = 'sent-on';
        $this->sortAsc = false;
    }

    public function reRun(CampaignTracker $campaignTracker): void
    {
        if ($campaignTracker->campaign->frequency !== CampaignFrequency::ONCE) {
            $this->error(__('Your communication matches an existing campaign.'));

            return;
        }

        if (blank($campaignTracker->campaign->template) || blank($campaignTracker->campaign->group)) {
            $this->error(__('This campaign no longer exists, please create new campaign.'));

            return;
        }

        $startDate = now()->lt(today()->setTime(16, 30)) ? today() : today()->addDay();

        $campaignTracker->campaign->replicate()
            ->fill([
                'start_date' => $startDate->toDateString(),
                'end_date' => null,
            ])
            ->save();

        $this->success(__('Your new campaign has been created!'));
    }

    public function exportConsumers(CampaignTracker $campaignTracker): ?BinaryFileResponse
    {
        $campaignTracker->loadMissing('consumers.reason');

        if ($campaignTracker->consumers->count() === 0) {
            $this->error(__('No consumers found. Please email help@younegotiate.com if this is an error.'));

            return null;
        }

        $this->success(__('Consumers exported!'));

        return Excel::download(
            new ConsumersExport($campaignTracker->consumers, $this->user),
            now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv',
            writerType: ExcelExcel::CSV
        );
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'sent-on' => 'created_at',
            'template-name' => 'template_name',
            'group-name' => 'group_name',
            'total-balance' => 'total_balance_of_consumers',
            'sent' => 'consumer_count',
            'delivered' => 'delivered_count',
            'delivered-percentage' => 'delivered_percentage',
            'opened' => 'clicks_count',
            'pif-offer' => 'pif_completed_count',
            'ppl-offer' => 'ppl_completed_count',
            'sent-offer' => 'custom_offer_count',
            default => 'created_at',
        };

        $data = [
            'company_id' => $this->user->company_id,
            'per_page' => $this->perPage,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.communications.campaign-tracker.list-page')
            ->with('campaignTrackers', app(CampaignTrackerService::class)->fetch($data))
            ->title(__('Campaign Tracker'));
    }
}
