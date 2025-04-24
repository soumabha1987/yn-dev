<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AutomatedCommunication\AutomatedTemplate;

use App\Enums\AutomatedTemplateType;
use App\Livewire\Creditor\Forms\AutomatedCommunication\AutomatedTemplateForm;
use App\Livewire\Creditor\Traits\DecodeImageToBase64;
use App\Models\AutomatedTemplate;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class EditPage extends Component
{
    use DecodeImageToBase64;

    public AutomatedTemplateForm $form;

    public function mount(AutomatedTemplate $automatedTemplate): void
    {
        $this->form->setFormData($automatedTemplate);
    }

    #[On('update-content')]
    public function updateContent(string $content): void
    {
        $this->form->fill(['content' => $content]);
    }

    public function update(): void
    {
        $validatedData = $this->form->validate();

        if ($this->form->automatedTemplate->type->value !== $validatedData['type']) {
            $this->error(__('Sorry, you can not edit template type'));

            return;
        }

        if ($validatedData['type'] === AutomatedTemplateType::SMS->value) {
            $validatedData['subject'] = null;
        }

        $validatedData['content'] = $this->decodeImageToBase64($validatedData['content'], 'automated-templates');

        $this->form->automatedTemplate->update($validatedData);

        $this->success(__('Your template has been updated!'));

        $this->redirectRoute('super-admin.automated-templates', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.automated-communication.automated-template.edit-page')
            ->title(__('Edit Automated Template'));
    }
}
