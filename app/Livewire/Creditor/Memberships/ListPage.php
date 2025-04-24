<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Memberships;

use App\Models\Membership;
use App\Services\MembershipService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class ListPage extends Component
{
    #[Url]
    public string $search = '';

    public function toggleActiveInactive(Membership $membership): void
    {
        $membership->update(['status' => ! $membership->status]);

        $this->success(__('Membership successfully :message!', [
            'message' => $membership->status ? 'displayed' : 'hidden',
        ]));
    }

    public function delete(bool $companyMembershipsExists, Membership $membership): void
    {
        if ($companyMembershipsExists) {
            $this->error(__('We cannot delete a membership plan that has one or more active members.'));

            $this->dispatch('close-confirmation-box');

            return;
        }

        $membership->delete();

        $this->success(__('Membership has been deleted!'));

        $this->dispatch('close-confirmation-box');
    }

    public function sort(int $item, int $position): void
    {
        $membership = Membership::query()->findOrFail($item);

        $membership->move($position);
    }

    public function render(): View
    {
        $data = [
            'search' => $this->search,
        ];

        return view('livewire.creditor.memberships.list-page')
            ->with('memberships', app(MembershipService::class)->fetch($data))
            ->title(__('Manage Memberships'));
    }
}
