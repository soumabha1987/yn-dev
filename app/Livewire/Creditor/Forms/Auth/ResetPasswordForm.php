<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Auth;

use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Form;

class ResetPasswordForm extends Form
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
