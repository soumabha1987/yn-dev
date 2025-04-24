<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Models\User;
use App\Rules\NamingRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class UserForm extends Form
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public ?User $user = null;

    public function setUp(User $user): void
    {
        $nameParts = explode(' ', $user->name);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $this->fill([
            'user' => $user,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email ?? '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'min:2', 'max:25', new NamingRule],
            'last_name' => ['required', 'string', 'min:2', 'max:25', new NamingRule],
            'email' => ['required', 'string', 'email', Rule::unique(User::class)->ignore($this->user?->id), 'min:2', 'max:255'],
        ];

        if ($this->user?->id === Auth::id()) {
            return $rules;
        }

        $rules = [
            ...$rules,
            'password' => [Rule::when($this->user, 'nullable', ['required', Password::defaults()])],
        ];

        return $rules;
    }
}
