<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Users;

use App\Livewire\Creditor\Traits\NewUserInvitation;
use App\Livewire\Creditor\Traits\Sortable;
use App\Mail\UserBlockedMail;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class ListPage extends Component
{
    use NewUserInvitation;
    use Sortable;

    protected UserService $userService;

    private User $user;

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'role';
        $this->user = Auth::user();
        $this->userService = app(UserService::class);
    }

    public function mount(): void
    {
        abort_if($this->user->parent_id !== null, Response::HTTP_NOT_FOUND);
    }

    public function delete(User $user): void
    {
        $closeConfirmBox = fn () => $this->dispatch('close-confirmation-box');

        if ($user->id === $this->user->id || $user->company_id !== $this->user->company_id) {
            $this->error(__('Sorry, your permissions do not allow you to delete this User.'));

            $closeConfirmBox();

            return;
        }

        $user->update([
            'email' => 'deleted-' . rand(1000, 9999) . '-' . $user->email,
            'blocked_at' => now(),
            'blocker_user_id' => $this->user->id,
        ]);

        Mail::to($user->email)->send(new UserBlockedMail($user));

        $this->success(__('User deleted.'));

        $closeConfirmBox();
    }

    public function resend(User $user): void
    {
        if (filled($user->email_verified_at)) {
            $this->error(__('This email belongs to an existing active user.'));

            return;
        }

        $this->sendInvitationMail($user->email);

        $this->success(__('User invitation link sent.'));
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'role' => 'id',
            'first-name' => 'name',
            'last-name' => 'last_name',
            'email' => 'email',
            'status' => 'id',
            default => 'id',
        };

        $data = [
            'company_id' => $this->user->company_id,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.users.list-page')
            ->with('users', $this->userService->fetch($data))
            ->title(__('Manage Users'));
    }
}
