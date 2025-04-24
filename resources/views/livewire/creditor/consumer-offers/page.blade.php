@use('Illuminate\Support\Number')
@use('App\Enums\NegotiationType')
@use('App\Enums\ConsumerStatus')
@use('App\Enums\Role')

<div>
    <div
        class="card"
        x-data="updateTitle"
        x-on:refresh-parent.window="$wire.$refresh"
    >
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $offers->isNotEmpty(),
                'justify-end' => $offers->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$offers" />
            <div class="flex flex-col sm:flex-row items-start sm:items-center w-full sm:w-auto gap-2">
                @if ($offers->isNotEmpty())
                    <x-form.button
                        wire:click="export"
                        wire:loading.attr="disabled"
                        type="button"
                        variant="primary"
                        class="space-x-2 disabled:opacity-50"
                    >
                        <span>{{ __('Export') }}</span>
                        <x-lucide-download class="size-5" wire:loading.remove wire:target="export" />
                        <x-lucide-loader-2 class="animate-spin size-5" wire:loading wire:target="export" />
                    </x-form.button>
                @endif
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                    <x-search-box
                        name="search"
                        wire:model.live.debounce.400="search"
                        placeholder="{{ __('Search') }}"
                        :description="__('Search by consumer name and account number')"
                    />
                </div>

                <label class="inline-flex space-x-2 items-center">
                    <input
                        type="checkbox"
                        class="form-checkbox is-basic size-4 sm:size-4.5 rounded border-slate-400/70 checked:bg-primary hover:border-primary focus:border-primary"
                        name="isRecentlyCompletedNegotiation"
                        wire:model.live.boolean="isRecentlyCompletedNegotiation"
                    />
                    <span>{{ __('Show Completed Negotiations') }}</span>
                </label>
            </div>
        </div>

        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table class="w-fit">
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="offer_date" :$sortAsc :$sortCol>{{ __('Offer Date') }}</x-table.th>
                        <x-table.th column="account_number" :$sortAsc :$sortCol>{{ __('Member Account#') }}</x-table.th>
                        <x-table.th column="consumer_name" :$sortAsc :$sortCol>{{ __('Consumer Name') }}</x-table.th>
                        <x-table.th column="original_account_name" :$sortAsc :$sortCol>{{ __('Account Name') }}</x-table.th>
                        <x-table.th column="sub_name" :$sortAsc :$sortCol>{{ __('Sub Account Name') }}</x-table.th>
                        <x-table.th column="placement_date" :$sortAsc :$sortCol>{{ __('Placement Date') }}</x-table.th>
                        <x-table.th column="offer_type" :$sortAsc :$sortCol>{{ __('Offer Type') }}</x-table.th>
                        <x-table.th column="payment_profile" :$sortAsc :$sortCol>{{ __('Payment Profile') }}</x-table.th>
                        <x-table.th>{{ __('Our Last Offer') }}</x-table.th>
                        <x-table.th column="consumer_last_offer" :$sortAsc :$sortCol>{{ __('Consumer Last Offer') }}</x-table.th>
                        @if ($isRecentlyCompletedNegotiation)
                            <x-table.th column="negotiated-balance" :$sortAsc :$sortCol>{{ __('Negotiated Balance') }}</x-table.th>
                        @endif
                        <x-table.th column="status" :$sortAsc :$sortCol>{{ __('Status') }}</x-table.th>
                        @if (! $isRecentlyCompletedNegotiation)
                            <x-table.th>{{ __('Actions') }}</x-table.th>
                        @endif
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($offers as $offer)
                        <x-table.tr>
                            <x-table.td>{{ $offer->consumerNegotiation->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    href="{{ route('manage-consumers.view', ['consumer' => $offer->consumerNegotiation->consumer_id]) }}"
                                    class="text-primary hover:cursor-pointer hover:underline underline-offset-2"
                                >
                                    {{ $offer->member_account_number }}
                                </a>
                            </x-table.td>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    href="{{ route('manage-consumers.view', ['consumer' => $offer->consumerNegotiation->consumer_id]) }}"
                                    class="text-primary hover:cursor-pointer hover:underline underline-offset-2"
                                >
                                    {{ str($offer->first_name . ' ' . $offer->last_name)->title()->headline()->toString() }}
                                </a>
                            </x-table.td>
                            <x-table.td>{{ str($offer->original_account_name)->title() }}</x-table.td>
                            <x-table.td>
                                {{  $offer->subclient_name ?? 'N/A' }}
                            </x-table.td>
                            <x-table.td>
                                {{ $offer->placement_date?->format('M d, Y') ?? 'N/A' }}
                            </x-table.td>
                            <x-table.td>
                                <span
                                    @class([
                                       'badge whitespace-nowrap rounded-md bg-primary/10 text-primary',
                                       'bg-success/10 text-success' => NegotiationType::PIF === $offer->consumerNegotiation->negotiation_type,
                                    ])
                                >
                                    {{ $offer->consumerNegotiation->negotiation_type->displayOfferBadge() }}
                                </span>
                            </x-table.td>
                            <x-table.td>
                                <span
                                    @class([
                                       'badge rounded-md bg-secondary/10 text-secondary',
                                       'bg-success/10 text-success' => $offer->payment_setup,
                                    ])
                                >
                                    {{ $offer->payment_setup ? __('Yes') : __('No') }}
                                </span>
                            </x-table.td>
                            <x-table.td>{{ Number::currency((float) $offer->ourLastOffer) }}</x-table.td>
                            <x-table.td>{{ Number::currency((float) $offer->consumerLastOffer) }}</x-table.td>
                            @if ($isRecentlyCompletedNegotiation)
                                <x-table.td>
                                    @if ($offer->consumerNegotiation->negotiation_type === NegotiationType::PIF)
                                        {{ Number::currency((float) ($offer->consumerNegotiation->counter_one_time_amount ?? $offer->consumerNegotiation->one_time_settlement ?? 0)) }}
                                    @elseif($offer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT)
                                        {{ Number::currency((float) ($offer->consumerNegotiation->counter_negotiate_amount ?? $offer->consumerNegotiation->negotiate_amount ?? 0)) }}
                                    @endif
                                </x-table.td>
                            @endif
                            @php
                                $status = function () use($offer): string|array {
                                    return match (true) {
                                        $offer->offer_accepted && $offer->counter_offer => [__('Counter offer accepted'), 'bg-secondary/10 text-secondary'],
                                        $offer->offer_accepted && $offer->custom_offer => [__('Negotiation offer accepted'), 'bg-primary/10 text-primary'],
                                        $offer->status === ConsumerStatus::PAYMENT_DECLINED => [__('Offer declined'), 'bg-error/10 text-error'],
                                        $offer->counter_offer => [__('Pending Consumer Response'), 'bg-info/10 text-info'],
                                        $offer->custom_offer => [__('New Offer!'), 'bg-success/10 text-success'],
                                        $offer->offer_accepted => [__('Completed auto negotiation'), 'bg-success/10 text-success'],
                                        default => 'N/A'
                                    };
                                }
                            @endphp
                            <x-table.td>
                                <span
                                     @class([
                                        'badge p-2 rounded-md whitespace-nowrap',
                                        data_get($status(), 1) => true,
                                    ])
                                >
                                    {{ data_get($status(), 0) }}
                                </span>
                            </x-table.td>
                            @if (! $isRecentlyCompletedNegotiation)
                                <x-table.td>
                                    <livewire:creditor.consumer-offers.view-offer
                                        :consumer="$offer"
                                        :key="str()->random(10)"
                                        :is-not-editable="$offer->counter_offer"
                                        lazy
                                    />
                                </x-table.td>
                            @endif
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="$isRecentlyCompletedNegotiation ? 9 : 10" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$offers" />
    </div>

    @script
        <script>
            Alpine.data('updateTitle', () => {
                return {
                    init() {
                        this.$wire.$watch('isRecentlyCompletedNegotiation', () => {
                            var title = @js(__('Consumer Offers'));

                            if (this.$wire.isRecentlyCompletedNegotiation) {
                                title = @js(__('Recently Completed Consumer Offers'))
                            }

                            this.$dispatch('update-title', [title]);
                        })
                    }
                }
            })
        </script>
    @endscript
</div>
