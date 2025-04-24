<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ConsumerOffers;

use App\Enums\NegotiationType;
use App\Exports\OpenNegotiationsExport;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\Consumer;
use App\Models\User;
use App\Services\Consumer\DiscountService;
use App\Services\ConsumerService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Page extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    public bool $isRecentlyCompletedNegotiation = false;

    protected ConsumerService $consumerService;

    protected DiscountService $discountService;

    private User $user;

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'offer-date';
        $this->sortAsc = false;
        $this->user = Auth::user();
        $this->consumerService = app(ConsumerService::class);
        $this->discountService = app(DiscountService::class);
    }

    public function updatedIsRecentlyCompletedNegotiation(): void
    {
        $this->resetPage();
    }

    public function export(): ?BinaryFileResponse
    {
        $offers = $this->consumerService->getOffers($this->setUp());

        if ($offers->isEmpty()) {
            $this->error(__('No consumer offers found to export.'));

            return null;
        }

        return Excel::download(
            new OpenNegotiationsExport($offers, $this->isRecentlyCompletedNegotiation),
            'consumer_offers_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv',
            writerType: ExcelExcel::CSV
        );
    }

    private function setConsumerLastOffer(Consumer $consumer): float
    {
        $consumerNegotiation = $consumer->consumerNegotiation;

        return $consumerNegotiation->negotiation_type === NegotiationType::PIF
            ? (float) $consumerNegotiation->one_time_settlement
            : (float) $consumerNegotiation->monthly_amount;
    }

    private function setOurLastOffer(Consumer $consumer): float|string
    {
        $consumerNegotiation = $consumer->consumerNegotiation;

        return match (true) {
            $consumer->counter_offer => $consumerNegotiation->negotiation_type === NegotiationType::PIF
                ? (float) $consumerNegotiation->counter_one_time_amount
                : (float) $consumerNegotiation->counter_monthly_amount,
            $consumerNegotiation->negotiation_type === NegotiationType::PIF => $this->discountService->fetchAmountToPayWhenPif($consumer)['discount'],
            $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT => $this->discountService
                ->fetchMonthlyAmount($consumer, $this->discountService->fetchAmountToPayWhenPpa($consumer)),
            default => null,
        };
    }

    private function offers(LengthAwarePaginator $consumers): LengthAwarePaginator
    {
        $consumers->each(function (Consumer $consumer): void {
            $consumer->setAttribute('consumerLastOffer', $this->setConsumerLastOffer($consumer));
            $consumer->setAttribute('ourLastOffer', $this->setOurLastOffer($consumer));
        });

        return $consumers;
    }

    private function setUp(): array
    {
        $column = match ($this->sortCol) {
            'offer_date' => 'created_at',
            'consumer_name' => 'consumer_name',
            'account_number' => 'member_account_number',
            'original_account_name' => 'original_account_name',
            'sub_name' => 'subclient_name',
            'placement_date' => 'placement_date',
            'offer_type' => 'negotiation_type',
            'payment_profile' => 'payment_setup',
            'consumer_last_offer' => 'consumer_last_offer',
            'negotiated-balance' => 'negotiated_amount',
            'status' => 'status',
            default => 'created_at'
        };

        return [
            'is_recently_completed_negotiation' => $this->isRecentlyCompletedNegotiation,
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ];
    }

    public function render(): View
    {
        $data = [
            ...$this->setUp(),
            'per_page' => $this->perPage,
        ];

        return view('livewire.creditor.consumer-offers.page')
            ->with('offers', $this->offers($this->consumerService->fetchOffers($data)))
            ->title(__('Consumer Offers'));
    }
}
