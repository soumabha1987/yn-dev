<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\ManageConsumers;

use App\Models\Consumer;
use Livewire\Form;

class ProfileForm extends Form
{
    public string $email = '';

    public string $mobile = '';

    public function setData(Consumer $consumer): void
    {
        $this->fill([
            'email' => $consumer->email1 ?? '',
            'mobile' => $consumer->mobile1 ?? '',
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:50'],
            'mobile' => ['required', 'string', 'phone:US'],
        ];
    }
}
