<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\PayTerms;

use App\Livewire\Creditor\Forms\PayTermsForm;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\GroupService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EditPage extends Component
{
    public PayTermsForm $form;

    private User $user;

    public int $id;

    public string $payTerms;

    public array $payTermsOption = [];

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function mount(): void
    {
        match ($this->payTerms) {
            'master-terms' => $this->form->fillMasterTerms($this->user, true),
            'sub-account-terms' => $this->form->fillSubAccountTerms($this->id, $this->user->company_id),
            'group-terms' => $this->form->fillGroupTerms($this->id, $this->user->company_id),
            default => null,
        };
    }

    public function update(): void
    {
        $validatedData = $this->form->validate();

        match ($this->payTerms) {
            'master-terms' => $this->updateMasterTerm($validatedData),
            'group-terms' => $this->updateGroupTerm($validatedData),
            'sub-account-terms' => $this->updateSubClientTerm($validatedData),
            default => null,
        };

        $this->success(__('Pay terms updated successfully!'));

        $this->redirectRoute('creditor.pay-terms', navigate: true);
    }

    private function updateMasterTerm(array $validatedData): void
    {
        app(CompanyService::class)->updateTerms($this->user->company_id, $validatedData);
    }

    private function updateSubClientTerm(array $validatedData): void
    {
        app(SubclientService::class)->updateTerms((int) $this->id, $validatedData);
    }

    private function updateGroupTerm(array $validatedData): void
    {
        app(GroupService::class)->updateTerms((int) $this->id, $validatedData);
    }

    public function render(): View
    {
        return view('livewire.creditor.pay-terms.edit-page')->title(__('Edit Pay Term Offer Profile(s)'));
    }
}
