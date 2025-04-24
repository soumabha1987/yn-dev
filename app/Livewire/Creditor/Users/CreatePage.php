<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Users;

use App\Enums\Role;
use App\Livewire\Creditor\Forms\UserForm;
use App\Livewire\Creditor\Traits\NewUserInvitation;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreatePage extends Component
{
    use NewUserInvitation;

    public UserForm $form;

    public bool $isNotCreateUser = false;

    public User $user;

    public function mount(): void
    {
        $this->user = Auth::user();

        abort_if($this->user->parent_id !== null, Response::HTTP_NOT_FOUND);

        $this->isNotCreateUser = app(UserService::class)->fetchCount($this->user->company_id) >= 3;

        $this->checkNotCreateUser();
    }

    public function create(): void
    {
        $this->checkNotCreateUser();

        $parentUser = $this->user;

        $validatedData = $this->form->validate();

        $validatedData['name'] = $validatedData['first_name'] . ' ' . $validatedData['last_name'];

        $user = User::query()->create([
            'name' => $validatedData['name'],
            'password' => $validatedData['password'],
            'email' => $validatedData['email'],
            'parent_id' => $parentUser->id,
            'company_id' => $parentUser->company_id,
            'subclient_id' => $parentUser->subclient_id,
            'email_verified_at' => null,
        ]);

        $user->assignRole(Role::CREDITOR);

        $this->sendInvitationMail($validatedData['email']);

        $this->success(__('User created.'));

        $this->redirectRoute('creditor.users', navigate: true);
    }

    private function checkNotCreateUser(): void
    {
        if ($this->isNotCreateUser) {
            $this->error(__('On this version, we offer up to 3 Users on each membership account.'));

            $this->redirectRoute('creditor.users');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.creditor.users.create-page')
            ->title(__('Create User'));
    }
}
