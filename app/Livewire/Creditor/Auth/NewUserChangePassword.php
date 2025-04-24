<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Auth;

use App\Livewire\Creditor\Forms\Auth\NewUserChangePasswordForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.guest-layout')]
class NewUserChangePassword extends Component
{
    public NewUserChangePasswordForm $form;

    #[Url]
    public string $email;

    public ?User $user;

    public function mount(): void
    {
        /** @var ?User $user */
        $user = User::query()->where('email', $this->email)->whereNull('email_verified_at')->first();

        abort_if(blank($user), Response::HTTP_NOT_FOUND);

        $this->form->email = $this->email;

        $this->user = $user;
    }

    public function changePassword(): void
    {
        $validatedData = $this->form->validate();

        $this->user->update([
            'password' => $validatedData['password'],
            'email_verified_at' => now(),
        ]);

        $this->success(__('Your password has been saved.'));

        $this->redirectRoute('login', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.auth.new-user-change-password')
            ->title(__('Change Password'));
    }
}
