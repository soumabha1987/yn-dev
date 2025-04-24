<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageH2HUsers;

use App\Enums\Role;
use App\Livewire\Creditor\Forms\H2HUsersForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    public H2HUsersForm $form;

    public bool $openModel = false;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function create(): void
    {
        $validatedData = $this->form->validate();

        $user = User::query()->create([
            'company_id' => $this->user->company_id,
            'parent_id' => $this->user->id,
            'is_h2h_user' => true,
            'name' => $validatedData['name'],
            'phone_no' => $validatedData['phone_no'],
            'password' => $validatedData['password'],
            'email' => $validatedData['email'],
            'email_verified_at' => now(),
        ]);

        $user->assignRole(Role::SUPERADMIN);

        $this->form->reset();

        $this->success(__('User created.'));

        $this->dispatch('close-dialog-box');
    }

    public function render(): View
    {
        return view('livewire.creditor.manage-h2h-users.create');
    }
}
