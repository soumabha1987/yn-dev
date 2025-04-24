<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManagePartners;

use App\Livewire\Creditor\Forms\PartnerForm;
use App\Models\Partner;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class CreatePage extends Component
{
    public PartnerForm $form;

    public function create(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['report_emails'] = collect(explode(',', $validatedData['report_emails']))
            ->map(fn (string $email): string => trim($email))
            ->unique();

        $validatedData['registration_code'] = Str::random(12);

        Partner::query()->create($validatedData);

        $this->success(__('Partner link created!'));

        $this->redirectRoute('super-admin.manage-partners', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.manage-partners.create-page')
            ->title('Create Partner');
    }
}
