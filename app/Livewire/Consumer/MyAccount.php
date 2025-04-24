<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Enums\ConsumerStatus;
use App\Livewire\Consumer\Traits\Agreement;
use App\Livewire\Consumer\Traits\CreditorDetails;
use App\Livewire\Consumer\Traits\MyAccounts\Conditions;
use App\Livewire\Consumer\Traits\MyAccounts\Offers;
use App\Livewire\Consumer\Traits\MyAccounts\StartOver;
use App\Models\Consumer;
use App\Models\Reason;
use App\Services\Consumer\ConsumerService;
use App\Services\Consumer\DiscountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class MyAccount extends Component
{
    use Agreement;
    use Conditions;
    use CreditorDetails;
    use Offers;
    use StartOver;

    #[Url(as: 'status')]
    public string $status = 'all';

    #[Url]
    public string $search = '';

    public float $minimumPpaDiscountedAmount = 0.00;

    public float $minimumPifDiscountedAmount = 0.00;

    public bool $sendCounterOffer = false;

    public bool $updateCommunicationModal = false;

    protected ConsumerService $consumerService;

    protected DiscountService $discountService;

    private Consumer $consumer;

    public function __construct()
    {
        $this->consumer = Auth::guard('consumer')->user();
        $this->consumerService = app(ConsumerService::class);
        $this->discountService = app(DiscountService::class);
    }

    public function mount(): void
    {
        if (! $this->consumer->consumerProfile->is_communication_updated) {
            $this->updateCommunicationModal = true;
        }

        if (Session::get('consumer_' . $this->consumer->id . '_communication_ignored', false)) {
            $this->updateCommunicationModal = false;
        }
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
    }

    public function dispute(Consumer $consumer): void
    {
        if (
            $consumer->last_name !== $this->consumer->last_name
            || $consumer->dob->toDateString() !== $this->consumer->dob->toDateString()
            || $consumer->last4ssn !== $this->consumer->last4ssn
        ) {
            $this->error(__('Something went wrong'));

            $this->dispatch('close-confirmation-box');

            return;
        }

        $consumer->update([
            'status' => ConsumerStatus::DISPUTE,
            'disputed_at' => now(),
        ]);

        $this->success(__('Profile updated.'));

        $this->dispatch('close-confirmation-box');
    }

    public function restart(Consumer $consumer): void
    {
        if (
            $consumer->last_name !== $this->consumer->last_name
            || $consumer->dob->toDateString() !== $this->consumer->dob->toDateString()
            || $consumer->last4ssn !== $this->consumer->last4ssn
        ) {
            $this->error(__('Sorry this consumer does not match credential'));

            $this->dispatch('close-confirmation-box');

            return;
        }

        $reasonId = null;

        if (! $consumer->reason->is_system) {
            $reasonId = $consumer->reason_id;
        }

        $consumer->update([
            'status' => ConsumerStatus::JOINED,
            'reason_id' => null,
            'disputed_at' => null,
        ]);

        Reason::query()->where('id', $reasonId)->delete();

        $this->success(__('Your account successfully restart'));
    }

    public function ignoreCommunication(): void
    {
        Session::put('consumer_' . $this->consumer->id . '_communication_ignored', true);
    }

    private function mutateAccounts(Collection $accounts): void
    {
        $accounts->each(function (Consumer $consumer): void {
            $consumer->setAttribute('creditorDetails', $this->setCreditorDetails($consumer));
            $consumer->setAttribute('accountConditions', $this->accountConditions($consumer));
            $consumer->setAttribute('negotiateCurrentAmount', $this->negotiationCurrentAmount($consumer));

            if ($consumer->getAttribute('accountConditions') === 'creditor_send_an_offer') {
                $consumer->setAttribute('offerDetails', $this->offerDetails($consumer));
            }

            if ($consumer->getAttribute('accountConditions') === 'pending_creditor_response') {
                $consumer->setAttribute('lastOffer', [
                    'account_profile_details' => $this->getAccountProfileDetails($consumer),
                    'offer_summary' => $this->getLastOffer($consumer),
                ]);
            }
        });
    }

    public function render(): View
    {
        $this->consumerService->markAsJoined($this->consumer);

        $priorityMap = ConsumerStatus::sortingPriorityByStates();
        $consumerStatuses = $this->consumerService
            ->fetchStates($this->consumer)
            ->flatMap(fn ($item) => $item)
            ->sortKeysUsing(fn ($key1, $key2) => $priorityMap[$key1] <=> $priorityMap[$key2])
            ->prepend(__('All'), 'all')
            ->all();

        $data = [
            'last_name' => $this->consumer->last_name,
            'dob' => $this->consumer->dob?->toDateString(),
            'last_four_ssn' => $this->consumer->last4ssn,
            'status' => $this->status,
            'search' => $this->search,
        ];

        $accounts = $this->consumerService->fetchAccounts($data);
        $this->mutateAccounts($accounts);

        return view('livewire.consumer.my-account')
            ->with(['consumerStatuses' => $consumerStatuses, 'accounts' => $accounts])
            ->title(__('Dashboard'));
    }
}
