<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AboutUs;

use App\Enums\CustomContentType;
use App\Livewire\Creditor\Forms\AboutUsForm;
use App\Livewire\Creditor\Traits\DecodeImageToBase64;
use App\Livewire\Creditor\Traits\ValidateMarkdown;
use App\Models\CustomContent;
use App\Models\User;
use App\Services\CustomContentService;
use App\Services\SetupWizardService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class CreateOrUpdatePage extends Component
{
    use DecodeImageToBase64;
    use ValidateMarkdown;

    public AboutUsForm $form;

    private User $user;

    public ?CustomContent $aboutUsContent;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function mount(): void
    {
        $this->aboutUsContent = app(CustomContentService::class)->fetchAboutUs(Auth::user()->company_id);

        if ($this->aboutUsContent) {
            $this->form->setValue($this->aboutUsContent);
        }
    }

    public function createOrUpdate(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['content'] = $this->decodeImageToBase64($validatedData['content'], 'about-us');

        $this->validateContent($validatedData['content']);

        $setupWizardService = app(SetupWizardService::class);

        $isNotCompletedSetupWizard = $setupWizardService->cachingRemainingRequireStepCount($this->user) !== 0;

        if (
            $isNotCompletedSetupWizard
            && $setupWizardService->isLastRequiredStepRemaining($this->user)
        ) {
            Session::put('show-wizard-completed-modal', true);

            Cache::forget('remaining-wizard-required-steps-' . $this->user->id);
        }

        CustomContent::query()->updateOrCreate(
            [
                'company_id' => $this->user->company_id,
                'type' => CustomContentType::ABOUT_US,
            ],
            [
                'content' => $validatedData['content'],
            ]
        );

        $this->success(__('Your About Us profile has been saved.'));

        if ($isNotCompletedSetupWizard) {
            $this->redirectRoute('home', navigate: true);

            return;
        }
    }

    public function render(): View
    {
        $title = filled($this->form->content) ? __('Member Company About Us') : __('Create About Us');
        $subtitle = filled($this->form->content) ? __('(visible to consumers)') : '';

        return view('livewire.creditor.about-us.create-or-update-page')
            ->title(view('components.title', compact('title', 'subtitle')));
    }
}
