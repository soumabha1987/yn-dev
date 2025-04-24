<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Memberships;

use App\Livewire\Creditor\Forms\MembershipForm;
use App\Models\Membership;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreatePage extends Component
{
    public MembershipForm $form;

    public function create(): void
    {
        Membership::query()->create($this->form->validate());

        $this->success(__('Membership has been created successfully!'));

        $this->redirectRoute('super-admin.memberships', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.memberships.create-page')->title(__('Create Membership'));
    }
}
