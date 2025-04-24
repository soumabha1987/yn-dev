<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\PayTerms;

use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\GroupService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    private User $user;

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'type';
        $this->user = Auth::user();
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function removeTerm(int $termId, string $termType): void
    {
        if ($termType === 'group') {
            Resolve(GroupService::class)->updateTerms($termId, [
                'pif_balance_discount_percent' => null,
                'ppa_balance_discount_percent' => null,
                'min_monthly_pay_percent' => null,
                'max_days_first_pay' => null,
                'minimum_settlement_percentage' => null,
                'minimum_payment_plan_percentage' => null,
                'max_first_pay_days' => null,
            ]);
        }

        if ($termType === 'sub account') {
            Resolve(SubclientService::class)->updateTerms($termId, [
                'pif_balance_discount_percent' => null,
                'ppa_balance_discount_percent' => null,
                'min_monthly_pay_percent' => null,
                'max_days_first_pay' => null,
                'minimum_settlement_percentage' => null,
                'minimum_payment_plan_percentage' => null,
                'max_first_pay_days' => null,
            ]);
        }

        $this->success(__('Negotiation terms have been successfully removed!'));

        $this->dispatch('close-confirmation-box');
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'name' => 'terms_name',
            'sub_id' => 'unique_identification_number',
            'type' => 'type',
            'pif-discount' => 'pif_balance_discount_percent',
            'ppa-discount' => 'ppa_balance_discount_percent',
            'min-monthly-amount' => 'min_monthly_pay_percent',
            'min-settlement-percentage' => 'minimum_settlement_percentage',
            'min-payment-plan-percentage' => 'minimum_payment_plan_percentage',
            'max-first-pay-days' => 'max_first_pay_days',
            'max-day' => 'max_days_first_pay',
            default => 'type',
        };

        $data = [
            'company_id' => $this->user->company_id,
            'per_page' => $this->perPage,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];

        return view('livewire.creditor.pay-terms.list-page')
            ->with('terms', app(CompanyService::class)->fetchPayTerms($data))
            ->title(__('Pay Term Offer(s)'));
    }
}
