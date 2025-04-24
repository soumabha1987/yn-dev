<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use App\Livewire\Creditor\Forms\PersonalizedLogoForm;
use App\Models\PersonalizedLogo;
use App\Models\User;
use App\Services\PersonalizedLogoService;
use App\Services\SetupWizardService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class PersonalizedLogoPage extends Component
{
    public PersonalizedLogoForm $form;

    protected PersonalizedLogoService $personalizedLogoService;

    protected SetupWizardService $setupWizardService;

    private ?PersonalizedLogo $personalizedLogo;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->personalizedLogoService = app(PersonalizedLogoService::class);
        $this->setupWizardService = app(SetupWizardService::class);
    }

    public function mount(): void
    {
        $this->personalizedLogo = $this->personalizedLogoService->findBySubclient($this->user->company_id, $this->user->subclient_id)
            ?: $this->personalizedLogoService->findByCompanyId($this->user->company_id);

        $this->form->init($this->personalizedLogo);
    }

    public function createOrUpdate(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['customer_communication_link'] = 'consumer';

        $this->personalizedLogoService->updateOrCreate($this->user->company_id, $this->user->subclient_id, $validatedData);

        Cache::flush();

        $this->dispatch('set-header-logo');

        $this->success(__('Personalized settings updated successfully!'));

        if ($this->setupWizardService->cachingRemainingRequireStepCount($this->user) !== 0) {
            $this->redirectRoute('home', navigate: true);
        }
    }

    public function resetAndSave(): void
    {
        $this->user->subclient_id
            ? $this->personalizedLogoService->deleteBySubclient($this->user->company_id, $this->user->subclient_id)
            : $this->personalizedLogoService->deleteByCompany($this->user->company_id);

        $this->form->reset();

        if ($this->user->subclient_id) {
            $this->form->init($this->personalizedLogoService->findByCompanyId($this->user->company_id));
        }

        Cache::flush();

        $this->dispatch('set-header-logo');

        $this->success(__('Personalized settings reset successfully!'));

        if ($this->setupWizardService->cachingRemainingRequireStepCount($this->user) !== 0) {
            $this->redirectRoute('home', navigate: true);
        }
    }

    public function render(): View
    {
        return view('livewire.creditor.personalized-logo-page')->title(__('My Personalized Logo & Link(s)'));
    }
}
