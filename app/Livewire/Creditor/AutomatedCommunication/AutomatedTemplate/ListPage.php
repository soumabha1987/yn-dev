<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AutomatedCommunication\AutomatedTemplate;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\AutomatedTemplate;
use App\Services\AutomatedTemplateService;
use App\Services\CommunicationStatusService;
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
        $this->sortCol = 'name';
    }

    public function delete(AutomatedTemplate $automatedTemplate): void
    {
        $isAutomatedTemplateExists = app(CommunicationStatusService::class)
            ->isAutomatedTemplateExists($automatedTemplate->id);

        $this->dispatch('close-confirmation-box');

        if ($isAutomatedTemplateExists) {
            $this->error(__('Template is attached to automated alert, cannot be deleted.'));

            return;
        }

        $automatedTemplate->delete();

        $this->success(__('Template deleted.'));
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'name' => 'name',
            'type' => 'type',
            'subject' => 'subject',
            default => ''
        };

        $data = [
            'per_page' => $this->perPage,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.automated-communication.automated-template.list-page')
            ->with('automatedTemplates', app(AutomatedTemplateService::class)->fetch($data))
            ->title(__('Automated Templates'));
    }
}
