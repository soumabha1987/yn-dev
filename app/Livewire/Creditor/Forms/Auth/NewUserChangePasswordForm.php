<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Auth;

use App\Livewire\Creditor\Auth\NewUserChangePassword;
use App\Rules\Recaptcha;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class NewUserChangePasswordForm extends Form
{
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $recaptcha = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var NewUserChangePassword $component */
        $component = $this->component;

        return [
            'email' => ['required', 'string', 'email', 'max:255', Rule::in($component->email)],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'recaptcha' => ['required', new Recaptcha],
        ];
    }
}
