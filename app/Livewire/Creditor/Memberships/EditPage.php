<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Memberships;

use App\Livewire\Creditor\Forms\MembershipForm;
use App\Models\Membership;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EditPage extends Component
{
    public MembershipForm $form;

    public Membership $membership;

    public function mount(): void
    {
        $this->form->init($this->membership);
    }

    public function update(): void
    {
        $this->membership->update($this->form->validate());

        $this->success(__('Membership has been updated!'));

        $this->redirectRoute('super-admin.memberships', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.memberships.edit-page')->title(__('Edit Membership'));
    }
}
