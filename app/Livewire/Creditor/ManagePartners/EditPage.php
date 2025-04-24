<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManagePartners;

use App\Livewire\Creditor\Forms\PartnerForm;
use App\Models\Partner;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EditPage extends Component
{
    public Partner $partner;

    public PartnerForm $form;

    public function mount(): void
    {
        $this->form->setup($this->partner);
    }

    public function update(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['report_emails'] = collect(explode(',', $validatedData['report_emails']))
            ->map(fn (string $email): string => trim($email))
            ->unique();

        $this->partner->update($validatedData);

        $this->success(__('Partner link updated.'));

        $this->redirectRoute('super-admin.manage-partners', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.manage-partners.edit-page')
            ->title('Edit Partner');
    }
}
