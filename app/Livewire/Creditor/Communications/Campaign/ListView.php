<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Communications\Campaign;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Models\User;
use App\Services\CampaignService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class ListView extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    public bool $isCreditor = false;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->withUrl = true;
        $this->sortCol = 'start-date';
        $this->sortAsc = false;
    }

    public function delete(Campaign $campaign): void
    {
        if ($campaign->company_id !== $this->user->company_id) {
            $this->error(__('Sorry you can not delete this campaign.'));

            $this->dispatch('close-confirmation-box');

            return;
        }

        DB::beginTransaction();

        try {
            $campaign->campaignTrackers()->cursor()->each(function (CampaignTracker $campaignTracker): void {
                $campaignTracker->campaignTrackerConsumers()->delete();
            });

            $campaign->campaignTrackers()->delete();

            $campaign->delete();

            $this->success(__('Campaign deleted.'));

            DB::commit();

        } catch (Exception) {
            DB::rollBack();

            $this->error(__('Apologies, this campaign cannot be delete.'));
        }

        $this->dispatch('close-confirmation-box');
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'start-date' => 'start_date',
            'end-date' => 'end_date',
            'template-name' => 'name',
            'template-type' => 'type',
            'group-name' => 'group_name',
            'frequency' => 'frequency',
            default => 'start_date'
        };

        $data = [
            'per_page' => $this->perPage,
            'company_id' => $this->user->company_id,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
            'search' => $this->search,
        ];

        return view('livewire.creditor.communications.campaign.list-view')
            ->with('campaigns', app(CampaignService::class)->fetch($data));
    }
}
