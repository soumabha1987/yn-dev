<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageH2HUsers;

use App\Livewire\Creditor\Forms\H2HUsersForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Edit extends Component
{
    public User $user;

    public H2HUsersForm $form;

    private User $authUser;

    public function __construct()
    {
        $this->authUser = Auth::user();
    }

    public function mount(): void
    {
        $this->form->init($this->user);
    }

    public function update(): void
    {
        Cache::set('user', $this->user->id);

        $validatedData = $this->form->validate();

        $this->user->update([
            'company_id' => $this->authUser->company_id,
            'parent_id' => $this->authUser->id,
            'is_h2h_user' => true,
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone_no' => $validatedData['phone_no'],
        ]);

        $this->success(__('User updated.'));

        Cache::forget('user');

        $this->dispatch('close-dialog-box');
    }

    public function render(): View
    {
        return view('livewire.creditor.manage-h2h-users.edit');
    }
}
