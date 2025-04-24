<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use App\Enums\CreditorCurrentStep;
use App\Enums\Role;
use App\Livewire\Creditor\Forms\ChangePasswordForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChangePasswordPage extends Component
{
    public ChangePasswordForm $form;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function updatePassword(): void
    {
        $validatedData = $this->form->validate();

        $this->user->update(['password' => $validatedData['newPassword']]);

        $this->form->reset();

        $this->success(__('Password updated.'));

        $this->redirectIntended(navigate: true);
    }

    public function render(): View
    {
        $layout = 'components.app-layout';

        if ($this->user->hasRole(Role::CREDITOR) && $this->user->company->current_step !== CreditorCurrentStep::COMPLETED->value) {
            $layout = 'components.profile-steps-layout';
        }

        return view('livewire.creditor.change-password-page')
            ->layout($layout)
            ->title(__('Change Password'));
    }
}
