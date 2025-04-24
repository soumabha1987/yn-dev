<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Exports\OpenNegotiationsExport;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\User;
use App\Services\Consumer\DiscountService;
use App\Services\ConsumerService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OpenNegotiations extends Component
{
    use Sortable;
    use WithoutUrlPagination;
    use WithPagination;

    public string $search = '';

    protected ConsumerService $consumerService;

    protected DiscountService $discountService;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->sortCol = 'consumer_name';
        $this->consumerService = app(ConsumerService::class);
        $this->discountService = app(DiscountService::class);
    }

    public function mount(): void
    {
        /** @var Company $company */
        $company = Auth::user()->company;

        if ($company->is_wizard_steps_completed) {
            Session::put('show-wizard-completed-modal', true);

            $company->update(['is_wizard_steps_completed' => false]);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function declineOffer(Consumer $consumer): void
    {
        $consumer->loadMissing(['consumerNegotiation', 'unsubscribe']);

        $consumer->consumerNegotiation->update(['offer_accepted' => false]);

        $consumer->update(['status' => ConsumerStatus::PAYMENT_DECLINED->value]);

        $consumer->scheduledTransactions()->delete();

        $consumer->paymentProfiles()->delete();

        try {
            $handleSuccess = fn () => $this->success(__('Consumer offer declined successfully.'));

            if ($consumer->unsubscribe) {
                Log::channel('daily')->info('When sending manual an email at that time consumer is not subscribe for that', [
                    'consumer_id' => $consumer->id,
                    'communication_code' => CommunicationCode::OFFER_DECLINED,
                ]);

                $this->dispatch('close-confirmation-box');

                $handleSuccess();

                return;
            }

            TriggerEmailAndSmsServiceJob::dispatch($consumer, CommunicationCode::OFFER_DECLINED);

            $handleSuccess();
        } catch (Exception $exception) {
            Log::channel('daily')->info('communication status error', [
                'message' => $exception->getMessage(),
                'stack trace string' => $exception->getTraceAsString(),
                'stack trace' => $exception->getTrace(),
            ]);

            $this->error(__('Something went wrong..'));
        }

        $this->dispatch('close-confirmation-box');
    }

    public function export(): ?BinaryFileResponse
    {
        $counterOffers = $this->consumerService->getOpenNegotiationOffers($this->setUp());

        if ($counterOffers->isEmpty()) {
            $this->error(__('Sorry, there are no accounts to download. If you feel this is an error, please email help@younegotiate.com'));

            return null;
        }

        return Excel::download(
            new OpenNegotiationsExport($counterOffers),
            'open_negotiations_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv',
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
            'status' => 'counter_offer',
            default => 'consumer_name'
        };

        return [
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

        $title = __('Dashboard');
        $subtitle = __('(Rolling 30-day view)');

        return view('livewire.creditor.dashboard.open-negotiation')
            ->with('consumers', $this->offers($this->consumerService->fetchOpenNegotiationOffers($data)))
            ->title(view('components.title', compact('title', 'subtitle')));
    }
}
