<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Models\PersonalizedLogo;
use Livewire\Form;

class PersonalizedLogoForm extends Form
{
    public string $primary_color = '#0079f2';

    public string $secondary_color = '#000000';

    public int $size = 320;

    public function init(?PersonalizedLogo $personalizedLogo): void
    {
        if (! $personalizedLogo) {
            return;
        }

        $this->fill([
            'personalizedLogo' => $personalizedLogo,
            'primary_color' => $personalizedLogo->primary_color,
            'secondary_color' => $personalizedLogo->secondary_color,
            'size' => $personalizedLogo->size ?? 320,
        ]);
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'primary_color' => ['required', 'string', 'hex_color'],
            'secondary_color' => ['required', 'string', 'hex_color'],
            'size' => ['required', 'integer', 'min:160', 'max:520'],
        ];
    }
}
