<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\TermsAndConditions;

use App\Enums\CustomContentType;
use App\Livewire\Creditor\Forms\TermsAndConditionsForm;
use App\Livewire\Creditor\Traits\DecodeImageToBase64;
use App\Livewire\Creditor\Traits\ValidateMarkdown;
use App\Models\CustomContent;
use App\Models\User;
use App\Services\CustomContentService;
use App\Services\SetupWizardService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class IndexPage extends Component
{
    use DecodeImageToBase64;
    use ValidateMarkdown;

    public TermsAndConditionsForm $form;

    public array $subclients = [];

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function mount(): void
    {
        $this->subclients = app(SubclientService::class)->fetchWithTermsAndCondition($this->user)->all();
    }

    public function createOrUpdate(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['content'] = $this->decodeImageToBase64($validatedData['content'], 'terms-and-conditions');

        $this->validateContent($validatedData['content']);

        $setupWizardService = app(SetupWizardService::class);

        $isNotCompletedSetupWizard = $setupWizardService->cachingRemainingRequireStepCount($this->user) !== 0;

        if (
            $isNotCompletedSetupWizard
            && $setupWizardService->isLastRequiredStepRemaining($this->user)
            && $validatedData['subclient_id'] === 'all'
        ) {
            Session::put('show-wizard-completed-modal', true);
            Cache::forget('remaining-wizard-required-steps-' . $this->user->id);
        }

        CustomContent::query()->updateOrCreate(
            [
                'company_id' => $this->user->company_id,
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
                'subclient_id' => $validatedData['subclient_id'] !== 'all' ? $validatedData['subclient_id'] : null,
            ],
            ['content' => $validatedData['content']]
        );

        $this->success(__('Terms & Conditions updates saved.'));

        $isOnlyMasterTermsAndCondition = count($this->subclients) === 1;

        if ($isNotCompletedSetupWizard && $isOnlyMasterTermsAndCondition) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->dispatch('refresh-list-view');

        $this->form->reset();
    }

    public function updatedFormSubclientId(): void
    {
        $customContentService = app(CustomContentService::class);

        if ($this->form->subclient_id === 'all') {
            $customContent = $customContentService->findByCompany($this->user->company_id);
            $this->form->content = $customContent->content ?? '';

            return;
        }

        $customContent = $customContentService->findBySubclient($this->user->company_id, (int) $this->form->subclient_id);

        $this->form->content = $customContent->content ?? '';
    }

    public function edit(CustomContent $customContent): void
    {
        if ($customContent->type !== CustomContentType::TERMS_AND_CONDITIONS || $customContent->company_id !== $this->user->company_id) {
            $this->error(__('Sorry you can not edit this terms and conditions.'));

            return;
        }

        $this->form->init($customContent);
    }

    public function render(): View
    {
        return view('livewire.creditor.terms-and-conditions.index-page')
            ->title(__('Terms & Conditions'));
    }
}
