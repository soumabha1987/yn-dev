<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Enums\MembershipFeatures;
use App\Enums\MembershipFrequency;
use App\Models\Membership;
use Illuminate\Validation\Rule;
use Livewire\Form;

class MembershipForm extends Form
{
    public ?Membership $membership = null;

    public string $name = '';

    public $fee = '';

    public $e_letter_fee = '';

    public $price = '';

    public $upload_accounts_limit = '';

    public string $frequency = '';

    public ?string $description = '';

    public array $features = [];

    public function init(Membership $membership): void
    {
        $this->fill([
            'membership' => $membership,
            'name' => $membership->name,
            'price' => $membership->price,
            'fee' => $membership->fee,
            'e_letter_fee' => $membership->e_letter_fee,
            'upload_accounts_limit' => $membership->upload_accounts_limit,
            'frequency' => $membership->frequency->value,
            'description' => $membership->description,
            'features' => $membership->getAttribute('features'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'max:40',
                Rule::unique(Membership::class)
                    ->whereNull('deleted_at')
                    ->ignore($this->membership?->id),
            ],
            'price' => ['required', 'numeric', 'min:0', 'regex:/^\d{1,6}(\.\d{0,2})?$/'],
            'fee' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d{1,6}(\.\d{0,2})?$/'],
            'e_letter_fee' => ['required', 'numeric', 'min:0.05', 'max:25', 'regex:/^\d{1,6}(\.\d{0,2})?$/'],
            'upload_accounts_limit' => ['required', 'integer', 'min:1', 'regex:/^\d+$/'],
            'frequency' => ['required', Rule::in(MembershipFrequency::values())],
            'description' => ['required', 'max:255'],
            'features' => ['nullable', 'array'],
            'features.*' => [Rule::in(MembershipFeatures::names())],
        ];
    }
}
