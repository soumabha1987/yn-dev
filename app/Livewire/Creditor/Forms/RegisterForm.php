<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Rules\Recaptcha;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Url;
use Livewire\Form;

class RegisterForm extends Form
{
    public string $subclient_name = '';

    public string $name = '';

    #[Url]
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
            'subclient_name' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'recaptcha' => ['required', new Recaptcha],
            'terms_and_conditions' => ['required', 'accepted'],
        ];
    }
}
