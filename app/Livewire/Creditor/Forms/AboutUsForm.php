<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Models\CustomContent;
use Livewire\Form;

class AboutUsForm extends Form
{
    public string $content = '';

    public function setValue(CustomContent $customContent): void
    {
        $this->fill([
            'content' => $customContent->content ?? '',
        ]);
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'content' => __('about-us content'),
        ];
    }
}
