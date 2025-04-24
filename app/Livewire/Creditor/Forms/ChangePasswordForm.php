<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class ChangePasswordForm extends Form
{
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => ['required', 'string', 'different:currentPassword', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'currentPassword' => 'current password',
            'newPassword' => 'new password',
            'confirmPassword' => 'confirm password',
        ];
    }
}
