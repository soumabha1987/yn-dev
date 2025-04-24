<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageH2HUsers;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $create = false;

    protected UserService $userService;

    private User $user;

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'name';
        $this->user = Auth::user();
        $this->userService = app(UserService::class);
    }

    public function delete(User $user): void
    {
        if (
            $this->user->id !== $user->parent_id ||
            ! $user->is_h2h_user ||
            $user->company_id !== $this->user->company_id
        ) {
            $this->error(__('Something went wrong...'));

            $this->dispatch('close-confirmation-box');

            return;
        }

        $user->delete();

        $this->success(__('H2H User deleted..'));

        $this->dispatch('close-confirmation-box');
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'name' => 'name',
            default => '',
        };

        $data = [
            'per_page' => $this->perPage,
            'user' => $this->user,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.manage-h2h-users.list-page')
            ->with('users', $this->userService->fetchH2H($data))
            ->title(__('Manage Users'));
    }
}
