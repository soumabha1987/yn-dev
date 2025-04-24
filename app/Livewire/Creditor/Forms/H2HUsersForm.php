<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class H2HUsersForm extends Form
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $phone_no = '';

    public function init(User $user): void
    {
        $this->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone_no' => $user->phone_no ?? '',
        ]);
    }

    public function rules(): array
    {
        $component = $this->component;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', Rule::unique(User::class, 'email')->ignore(Cache::get('user'))],
            'password' => ['sometimes', Rule::when(! ($component->user ?? false), ['required', 'string', Password::defaults()])],
            'phone_no' => ['nullable', 'string', 'phone:US'],
        ];
    }
}
