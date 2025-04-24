<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Auth;

use App\Rules\Recaptcha;
use Livewire\Form;

class ForgotPasswordForm extends Form
{
    public string $email = '';

    public string $recaptcha = '';

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'recaptcha' => ['required', new Recaptcha],
        ];
    }
}
