<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard\Stats;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\ConsumerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class PaymentPlanPage extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->withUrl = true;
        $this->sortCol = 'consumer_name';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'consumer_name' => 'consumer_name',
            'member_account_number' => 'member_account_number',
            'sub_account' => 'subclient_name',
            'current_balance' => 'current_balance',
            'profile_created_on' => 'payment_profile_created_on',
            default => ''
        };

        $data = [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'search' => $this->search,
            'per_page' => $this->perPage,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.dashboard.stats.payment-plan-page')
            ->with('consumers', app(ConsumerService::class)->fetchOnPaymentPlan($data))
            ->title(__('Consumers on Payment Plans'));
    }
}
