<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Auth;

use App\Models\User;
use App\Rules\NamingRule;
use App\Rules\Recaptcha;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class RegisterForm extends Form
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $recaptcha = '';

    public bool $terms_and_conditions = false;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:25', new NamingRule],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'recaptcha' => ['required', new Recaptcha],
            'terms_and_conditions' => ['required', 'accepted'],
        ];
    }
}
