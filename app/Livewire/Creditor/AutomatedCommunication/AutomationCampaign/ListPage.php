<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AutomatedCommunication\AutomationCampaign;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\AutomationCampaign;
use App\Services\AutomationCampaignService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'status';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updateEnabled(AutomationCampaign $automationCampaign): void
    {
        $automationCampaign->update(['enabled' => ! $automationCampaign->enabled]);

        $this->success(__('Automation campaign :value successfully!!', [
            'value' => $automationCampaign->enabled ? __('enabled') : __('disabled'),
        ]));
    }

    public function delete(AutomationCampaign $automationCampaign): void
    {
        $automationCampaign->delete();

        $this->dispatch('close-confirmation-box');

        $this->success(__('Your scheduled alerts are cancelled.'));
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'status' => 'code',
            'frequency' => 'frequency',
            'email_template_name' => 'automated_email_template_name',
            'sms_template_name' => 'automated_sms_template_name',
            'enabled' => 'enabled',
            default => ''
        };

        $data = [
            'per_page' => $this->perPage,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.automated-communication.automation-campaign.list-page')
            ->with('automationCampaigns', app(AutomationCampaignService::class)->fetch($data))
            ->title(__('Automation Campaigns'));
    }
}
