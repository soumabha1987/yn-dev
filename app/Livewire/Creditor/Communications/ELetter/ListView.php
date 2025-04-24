<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Communications\ELetter;

use App\Enums\Role;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\Template;
use App\Models\User;
use App\Services\TemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class ListView extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    private bool $isCreditor = false;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();

        $this->isCreditor = $this->user->hasRole(Role::CREDITOR);

        $this->withUrl = true;
        $this->sortCol = 'created-on';
        $this->sortAsc = false;
    }

    public function delete(Template $template): void
    {
        $type = $template->type->value;

        $template->delete();

        $this->dispatch('close-confirmation-box');

        $this->dispatch('reset-parent');

        $this->resetPage();

        if ($this->isCreditor) {
            $this->success(__('eLetter deleted.'));

            return;
        }

        $this->success(__(':type deleted successfully!', ['type' => ucfirst($type)]));
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'created-on' => 'created_at',
            'template-name' => 'name',
            'type' => 'type',
            'created-by' => 'user_name',
            default => 'created_at'
        };

        $data = [
            'is_creditor' => $this->user->hasRole(Role::CREDITOR),
            'company_id' => $this->user->company_id,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
            'per_page' => $this->perPage,
        ];

        return view('livewire.creditor.communications.e-letter.list-view')
            ->with('eLetters', app(TemplateService::class)->fetchELetter($data));
    }
}
