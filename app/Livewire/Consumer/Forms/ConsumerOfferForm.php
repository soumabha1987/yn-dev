<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Forms;

use App\Rules\SingleSpace;
use Livewire\Form;

class ConsumerOfferForm extends Form
{
    public string $counter_first_pay_date = '';

    public string $monthly_amount = '';

    public string $counter_note = '';

    public function rules(): array
    {
        return [
            'counter_first_pay_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'monthly_amount' => ['required', 'numeric', 'gt:0'],
            'counter_note' => ['nullable', 'string', 'max:100', new SingleSpace],
        ];
    }
}
