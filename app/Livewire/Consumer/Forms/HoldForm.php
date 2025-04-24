<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Forms;

use App\Models\Consumer;
use Livewire\Form;

class HoldForm extends Form
{
    public string $restart_date = '';

    public string $hold_reason = '';

    public function init(Consumer $consumer): void
    {
        $this->fill([
            'restart_date' => $consumer->restart_date?->toDateString() ?? today()->addDay()->toDateString(),
            'hold_reason' => $consumer->hold_reason ?? '',
        ]);
    }

    public function rules(): array
    {
        return [
            'restart_date' => ['required', 'date', 'date_format:Y-m-d', 'after:today'],
            'hold_reason' => ['required', 'string', 'max:255'],
        ];
    }
}
