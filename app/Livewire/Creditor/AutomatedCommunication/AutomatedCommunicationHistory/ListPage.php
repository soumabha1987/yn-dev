<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AutomatedCommunication\AutomatedCommunicationHistory;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Services\AutomatedCommunicationHistoryService;
use App\Services\CompanyService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url('search')]
    public string $searchTerm = '';

    #[Url]
    public string $communicationCode = '';

    #[Url]
    public string $templateType = '';

    public ?float $totalCost = null;

    #[Url]
    public string $company = '';

    public string $subclient = '';

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'code';
    }

    public function resetFilters(): void
    {
        $this->resetPage();

        $this->reset();
    }

    public function updated(): void
    {
        $this->reset('totalCost');

        $this->resetPage();
    }

    public function updatedCompany(): void
    {
        $this->reset('subclient');
    }

    private function automationCommunicationHistoryList(): LengthAwarePaginator
    {
        $column = match ($this->sortCol) {
            'code' => 'communication_code',
            'company_name' => 'company_name',
            'consumer_name' => 'consumer_name',
            'template_type' => 'automated_template_type',
            'template_name' => 'automated_template_name',
            'cost' => 'cost',
            'status' => 'status',
            default => ''
        };

        $data = app(AutomatedCommunicationHistoryService::class)
            ->fetch([
                'per_page' => $this->perPage,
                'search_term' => $this->searchTerm,
                'column' => $column,
                'direction' => $this->sortAsc ? 'ASC' : 'DESC',
                'communication_code' => $this->communicationCode,
                'template_type' => $this->templateType,
                'status' => $this->status,
                'company' => $this->company,
                'subclient' => $this->subclient,
            ]);

        $this->totalCost = (float) $data->getCollection()->sum('cost');

        return $data;
    }

    public function render(): View
    {
        return view('livewire.creditor.automated-communication.automated-communication-history.list-page')
            ->with([
                'automatedCommunicationHistories' => $this->automationCommunicationHistoryList(),
                'companies' => app(CompanyService::class)->fetchForFilters(),
                'subclients' => app(SubclientService::class)->fetchForSelectionBox((int) $this->company),
            ])
            ->title(__('Automated Communication History'));
    }
}
