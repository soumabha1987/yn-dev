<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Communications;

use App\Enums\Role;
use App\Enums\TemplateType;
use App\Models\Template;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ELetterForm extends Form
{
    public ?Template $template = null;

    public string $name = '';

    public string $type = TemplateType::EMAIL->value;

    public ?string $subject = '';

    public string $description = '';

    public string $smsDescription = '';

    public function init(Template $template): void
    {
        $this->fill([
            'template' => $template,
            'name' => $template->name,
            'type' => $template->type->value,
            'subject' => $template->subject,
            'description' => $template->type !== TemplateType::SMS ? $template->description : '',
            'smsDescription' => $template->type === TemplateType::SMS ? $template->description : '',
        ]);
    }

    public function rules(): array
    {
        $isSuperAdmin = Auth::user()->hasRole(Role::SUPERADMIN);

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Template::class)
                    ->whereNull('deleted_at')
                    ->where('user_id', Auth::id())
                    ->ignore($this->template?->id),
            ],
            'type' => [
                'nullable',
                Rule::requiredIf($isSuperAdmin),
                'string',
                'min:3',
                'max:9',
                Rule::when(
                    $isSuperAdmin,
                    [Rule::in([TemplateType::EMAIL->value, TemplateType::SMS->value])],
                    [Rule::in([TemplateType::E_LETTER->value])]
                ),
            ],
        ];

        if ($isSuperAdmin && $this->type === TemplateType::EMAIL->value) {
            $rules['subject'] = ['required', 'string', 'max:255'];
        }

        if (in_array($this->type, [TemplateType::EMAIL->value, TemplateType::E_LETTER->value], true)) {
            $rules['description'] = ['required', 'string'];
        }

        if ($this->type === TemplateType::SMS->value) {
            $rules['smsDescription'] = ['required', 'string'];
        }

        return $rules;
    }
}
