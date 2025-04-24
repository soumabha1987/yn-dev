<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Memberships;

use App\Livewire\Traits\WithPagination;
use App\Models\Membership;
use App\Services\CompanyMembershipService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ShowPage extends Component
{
    use WithPagination;

    public Membership $membership;

    public function render(): View
    {
        $data = [
            'membership_id' => $this->membership->id,
            'per_page' => $this->perPage,
        ];

        return view('livewire.creditor.memberships.show-page')
            ->with('companyMemberships', app(CompanyMembershipService::class)->fetchByMembership($data))
            ->title(__('Membership Details'));
    }
}
