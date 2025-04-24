<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\AutomatedCommunication;

use App\Enums\AutomatedTemplateType;
use App\Models\AutomatedTemplate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\Unique;
use Livewire\Form;

class AutomatedTemplateForm extends Form
{
    public ?AutomatedTemplate $automatedTemplate = null;

    public string $name = '';

    public string $type = AutomatedTemplateType::EMAIL->value;

    public ?string $subject = '';

    public string $content = '';

    public function setFormData(?AutomatedTemplate $automatedTemplate): void
    {
        $this->fill([
            'automatedTemplate' => $automatedTemplate,
            'name' => $automatedTemplate->name,
            'type' => $automatedTemplate->type->value,
            'subject' => $automatedTemplate->subject,
            'content' => $automatedTemplate->content,
        ]);
    }

    /**
     * @return array<string, array<int, string | Unique | In | RequiredIf>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(AutomatedTemplate::class)->ignore($this->automatedTemplate)],
            'type' => ['required', 'string', 'min:3', 'max:5', Rule::in(AutomatedTemplateType::values())],
            'subject' => [Rule::requiredIf($this->type === AutomatedTemplateType::EMAIL->value)],
            'content' => ['required', 'string'],
        ];
    }
}
