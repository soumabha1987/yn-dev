<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\TermsAndConditions;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\CustomContent;
use App\Models\User;
use App\Services\CustomContentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListView extends Component
{
    use Sortable;
    use WithPagination;

    private User $user;

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'type';
        $this->sortAsc = true;
        $this->user = Auth::user();
    }

    public function delete(CustomContent $customContent): void
    {
        if ($customContent->subclient_id === null) {
            $this->error(__('YN requires Master Terms and Conditions template on all member accounts. You can edit, however not delete.'));

            $this->dispatch('close-confirmation-box');

            return;
        }

        $customContent->delete();

        $this->success(__('Terms and Conditions template deleted.'));

        $this->dispatch('close-confirmation-box');
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'type' => 'subclient_id',
            'name' => 'subclient_name',
            default => 'created_at',
        };

        $data = [
            'company_id' => $this->user->company_id,
            'per_page' => $this->perPage,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.terms-and-conditions.list-view')
            ->with(['termsAndConditions' => app(CustomContentService::class)->fetchTermsAndConditions($data)]);
    }
}
