@use('App\Enums\NegotiationType')
@use('Illuminate\Support\Number')

<x-dashboard.index-page route-name="creditor.dashboard">
    <div x-on:refresh-parent.window="$wire.$refresh">
        <div class="sm:flex items-center justify-between space-y-2 sm:space-y-0 sm:space-x-2 py-3 px-4">
            <h2 class="text-md text-black font-semibold lg:text-lg">
                {{ __('Open Negotiations') }}
            </h2>

            <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
                <x-table.per-page-count :items="$consumers" />
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                    <x-search-box
                        name="search"
                        wire:model.live.debounce.400="search"
                        placeholder="{{ __('Search') }}"
                        :description="__('You can search by consumer name and account number')"
                    />
                </div>

                @if ($consumers->isNotEmpty())
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
            </div>
        </div>

        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table>
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
                        <x-table.th column="status" :$sortAsc :$sortCol>{{ __('Status') }}</x-table.th>
                        <x-table.th>{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse($consumers as $consumer)
                        <x-table.tr>
                            <x-table.td class="text-center">{{ $consumer->consumerNegotiation->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td class="text-center">
                                <a
                                    wire:navigate
                                    href="{{ route('manage-consumers.view', ['consumer' => $consumer->consumerNegotiation->consumer_id]) }}"
                                    class="text-primary hover:cursor-pointer hover:underline underline-offset-2"
                                >
                                    {{ $consumer->member_account_number }}
                                </a>
                            </x-table.td>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    href="{{ route('manage-consumers.view', ['consumer' => $consumer->id]) }}"
                                    class="hover:underline text-primary"
                                >
                                    {{ str($consumer->first_name . ' ' . $consumer->last_name)->title() }}
                                </a>
                            </x-table.td>
                            <x-table.td class="text-center">{{ $consumer->original_account_name ?? 'N/A' }}</x-table.td>
                            <x-table.td class="text-center">{{ $consumer->subclient_name ?? 'N/A' }}</x-table.td>
                            <x-table.td class="text-center">{{ $consumer->placement_date?->formatWithTimezone() ?? 'N/A' }}</x-table.td>
                            <x-table.td>
                                <span @class([
                                    'badge bg-primary/20 text-primary whitespace-nowrap',
                                    'bg-success/20 text-success' => $consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT,
                                ])>
                                    {{ $consumer->consumerNegotiation->negotiation_type->displayOfferBadge() }}
                                </span>
                            </x-table.td>
                            <x-table.td class="text-center">
                                <span
                                    @class([
                                    'badge rounded-md bg-secondary/10 text-secondary',
                                    'bg-success/10 text-success' => $consumer->payment_setup,
                                    ])
                                >
                                    {{ $consumer->payment_setup ? __('Yes') : __('No') }}
                                </span>
                            </x-table.td>
                            <x-table.td>{{ Number::currency((float) $consumer->ourLastOffer) }}</x-table.td>
                            <x-table.td>{{ Number::currency((float) $consumer->consumerLastOffer) }}</x-table.td>
                            <x-table.td>
                                <span
                                    @class([
                                    'badge rounded-md bg-info/10 text-info whitespace-nowrap',
                                    'bg-success/10 text-success' => $consumer->counter_offer,
                                    ])
                                >
                                    {{ $consumer->counter_offer ? __('Pending Consumer Response') : __('New Offer!') }}
                                </span>
                            </x-table.td>
                            <x-table.td>
                                <livewire:creditor.consumer-offers.view-offer
                                    :consumer="$consumer"
                                    :key="str()->random(10)"
                                    :is-not-editable="$consumer->counter_offer"
                                />
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="12" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$consumers" />
    </div>
</x-dashboard.index-page>
