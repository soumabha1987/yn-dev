<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Users;

use App\Livewire\Creditor\Forms\UserForm;
use App\Livewire\Creditor\Traits\NewUserInvitation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EditPage extends Component
{
    use NewUserInvitation;

    public UserForm $form;

    public ?User $user;

    public function mount(): void
    {
        abort_if(Auth::user()->parent_id !== null, Response::HTTP_NOT_FOUND);

        $this->form->setUp($this->user);
    }

    public function update(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['name'] = $validatedData['first_name'] . ' ' . $validatedData['last_name'];

        $this->user->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
        ]);

        if ($this->user->wasChanged('email') && $this->user->id !== Auth::id()) {
            $this->sendInvitationMail($validatedData['email']);

            $this->user->update(['email_verified_at' => null]);
        }

        $this->success(__('User account updated.'));

        $this->redirectRoute('creditor.users', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.users.edit-page')
            ->title(__('Edit User'));
    }
}
