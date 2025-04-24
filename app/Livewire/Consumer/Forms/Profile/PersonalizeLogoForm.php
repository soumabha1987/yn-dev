<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Forms\Profile;

use Illuminate\Http\UploadedFile;
use Livewire\Form;
use stdClass;

class PersonalizeLogoForm extends Form
{
    public string $primary_color = '#2563eb';

    public string $secondary_color = '#000000';

    public ?UploadedFile $image = null;

    public function init(stdClass $personalizedLogo): void
    {
        $this->fill([
            'primary_color' => $personalizedLogo->primary_color,
            'secondary_color' => $personalizedLogo->secondary_color,
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
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }
}
