<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use Livewire\Form;

class MembershipInquiryForm extends Form
{
    public string $description = '';

    public $accounts_in_scope = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:1000'],
            'accounts_in_scope' => ['required', 'integer', 'gt:0', 'regex:/^\d+$/'],
        ];
    }
}
