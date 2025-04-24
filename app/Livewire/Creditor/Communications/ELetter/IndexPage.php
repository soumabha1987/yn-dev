<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Communications\ELetter;

use App\Enums\Role;
use App\Enums\TemplateType;
use App\Livewire\Creditor\Forms\Communications\ELetterForm;
use App\Livewire\Creditor\Traits\DecodeImageToBase64;
use App\Livewire\Creditor\Traits\ValidateMarkdown;
use App\Models\Template;
use App\Models\User;
use App\Services\SetupWizardService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class IndexPage extends Component
{
    use DecodeImageToBase64;
    use ValidateMarkdown;

    public ELetterForm $form;

    private bool $isCreditor = false;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();

        $this->isCreditor = $this->user->hasRole(Role::CREDITOR);
    }

    public function mount(): void
    {
        if ($this->isCreditor && app(SetupWizardService::class)->getRemainingStepsCount($this->user) !== 0) {
            $this->redirectRoute('creditor.setup-wizard', navigate: true);
        }
    }

    public function createOrUpdate(): void
    {
        $validatedData = $this->form->validate();

        if ($this->form->template && ($this->form->template->type->value !== $validatedData['type'])) {
            $this->error(__('Sorry, you can not edit template type'));

            return;
        }

        if ($validatedData['type'] !== TemplateType::SMS->value) {
            $this->validateContent($validatedData['description'], 'form.description');
        }

        $description = $validatedData['type'] === TemplateType::SMS->value
            ? $validatedData['smsDescription']
            : $this->decodeImageToBase64($validatedData['description'], 'templates');

        Arr::forget($validatedData, 'smsDescription');

        Template::query()->updateOrCreate(
            ['id' => $this->form->template?->id],
            [
                ...array_filter($validatedData),
                'description' => $description,
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
                'type' => $this->user->hasRole(Role::SUPERADMIN) ? $validatedData['type'] : TemplateType::E_LETTER,
            ]
        );

        $this->dispatch('refresh-list-view');

        if ($this->isCreditor) {
            $this->success(__('eLetter template :status.', ['status' => $this->form->template ? 'updated' : 'saved']));

            $this->form->reset();

            return;
        }

        $this->success(__('template :status', ['status' => $this->form->template ? 'updated.' : 'saved.']));

        $this->form->reset();
    }

    public function edit(Template $template): void
    {
        if ($template->company_id !== $this->user->company_id) {
            $this->error(__('You do not have permission to edit this template.'));

            return;
        }

        $this->form->init($template);

        $this->dispatch('email-subject', $template->subject);

        $this->dispatch('update-title', [__('Edit Template')]);
    }

    #[On('update-description')]
    public function updateDescription(string $description): void
    {
        $this->form->description = $description;
    }

    #[On('update-sms-description')]
    public function updateSMSDescription(string $smsDescription): void
    {
        $this->form->smsDescription = $smsDescription;
    }

    #[On('reset-parent')]
    public function resetForm(): void
    {
        $this->form->reset();
    }

    public function render(): View
    {
        if ($this->isCreditor) {
            $this->form->type = TemplateType::E_LETTER->value;
        }

        return view('livewire.creditor.communications.e-letter.index-page')
            ->title(__('Create Template'));
    }
}
