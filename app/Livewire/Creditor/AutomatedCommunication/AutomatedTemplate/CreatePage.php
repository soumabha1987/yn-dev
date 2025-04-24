<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AutomatedCommunication\AutomatedTemplate;

use App\Enums\AutomatedTemplateType;
use App\Livewire\Creditor\Forms\AutomatedCommunication\AutomatedTemplateForm;
use App\Livewire\Creditor\Traits\DecodeImageToBase64;
use App\Models\AutomatedTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Livewire\Component;

class CreatePage extends Component
{
    use DecodeImageToBase64;

    public AutomatedTemplateForm $form;

    public function create(): void
    {
        $validatedData = $this->form->validate();

        if ($validatedData['type'] === AutomatedTemplateType::SMS->value) {
            Arr::forget($validatedData, 'subject');
        }

        $validatedData['content'] = $this->decodeImageToBase64($validatedData['content'], 'automated-templates');

        $validatedData['user_id'] = auth()->id();

        AutomatedTemplate::query()->create($validatedData);

        $this->success(__('Your template has been saved!'));

        $this->redirectRoute('super-admin.automated-templates', navigate: true);
    }

    #[On('update-content')]
    public function updateContent(string $content): void
    {
        $this->form->fill(['content' => $content]);
    }

    public function render(): View
    {
        return view('livewire.creditor.automated-communication.automated-template.create-page')
            ->title(__('Create Automated Template'));
    }
}
