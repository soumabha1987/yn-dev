<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Subclient;

use App\Enums\Role;
use App\Livewire\Creditor\Forms\SubclientForm;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\Subclient;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    public SubclientForm $form;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $dialogOpen = false;

    protected SubclientService $subclientService;

    private User $user;

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'name';
        $this->user = Auth::user();
        $this->subclientService = app(SubclientService::class);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function createOrUpdate(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['company_id'] = (int) ($this->user->hasRole(Role::CREDITOR)
            ? $this->user->company_id
            : $validatedData['company_id']);

        Subclient::query()
            ->updateOrCreate(['id' => $this->form->subclient?->id], $validatedData);

        $this->reset('dialogOpen');

        $this->success(__(
            ':subclient is :status successfully',
            [
                'subclient' => $validatedData['subclient_name'],
                'status' => $this->form->subclient?->id ? 'updated' : 'created',
            ]
        ));

        $this->form->reset();
    }

    public function delete(Subclient $subclient): void
    {
        $subclient->consumers()->update(['subclient_id' => null]);

        $subclient->delete();

        $this->success(__('Sub Account deleted.'));
    }

    public function edit(Subclient $subclient): void
    {
        $this->resetValidation();
        $this->form->init($subclient);
    }

    public function formReset(): void
    {
        $this->form->reset();
        $this->resetValidation();

        $this->dialogOpen = true;
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'name' => 'subclient_name',
            'created_on' => 'created_at',
            'company_name' => 'company_name',
            'pay_terms' => 'pay_terms',
            'unique_identification_number' => 'unique_identification_number',
            default => 'subclient_name'
        };

        $data = [
            'company_id' => $this->user->hasRole(Role::CREDITOR) ? $this->user->company_id : null,
            'per_page' => $this->perPage,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
            'search' => $this->search,
        ];

        return view('livewire.creditor.subclient.list-page')
            ->with([
                'subclients' => $this->subclientService->fetchByCompany($data),
                'companies' => app(CompanyService::class)->fetchForSelectionBox(),
            ])
            ->title(__('Sub Accounts'));
    }
}
