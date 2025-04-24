<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AutomatedCommunication\CommunicationStatus;

use App\Livewire\Creditor\Traits\Sortable;
use App\Services\CommunicationStatusService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class ListPage extends Component
{
    use Sortable;

    #[Url]
    public string $search = '';

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'priority';
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'status' => 'code',
            'trigger_type' => 'trigger_type',
            'email_template_name' => 'automated_email_template_name',
            'sms_template_name' => 'automated_sms_template_name',
            default => 'priority'
        };

        $data = [
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.automated-communication.communication-status.list-page')
            ->with('communicationStatuses', app(CommunicationStatusService::class)->fetch($data))
            ->title(__('Communication Statuses'));
    }
}
