@use('App\Enums\NegotiationType')
@use('Illuminate\Support\Number')

<x-dashboard.index-page route-name="creditor.dashboard.completed-negotiations">
    <div class="sm:flex items-center justify-between space-y-2 sm:space-y-0 sm:space-x-2 py-3 px-4">
        <h2 class="text-md text-black font-semibold lg:text-lg">
            {{ __('Negotiated/Pending Payment') }}
        </h2>
        <div class="sm:flex flex-col sm:flex-row items-start sm:items-center sm:space-x-2 space-y-2 sm:space-y-0">
            <x-table.per-page-count :items="$consumers" />
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                <x-search-box
                    name="search"
                    wire:model.live.debounce.400="search"
                    placeholder="{{ __('Search') }}"
                    :description="__('You can search by its consumer name and account number')"
                />
            </div>
            @if ($consumers->isNotEmpty())
                <x-form.button
                    wire:click="export"
                    wire:loading.attr="disabled"
                    wire:target="export"
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
    <div
        class="is-scrollbar-hidden min-w-full overflow-x-auto"
        x-on:refresh-page.window="$wire.$refresh"
    >
        <x-table>
            <x-slot name="tableHead">
                <x-table.tr>
                    <x-table.th column="consumer-name" :$sortAsc :$sortCol>{{ __('Consumer Name') }}</x-table.th>
                    <x-table.th column="master-account-number" :$sortAsc :$sortCol>{{ __('Account #') }}</x-table.th>
                    <x-table.th column="account-name" :$sortAsc :$sortCol>{{ __('Account Name') }}</x-table.th>
                    <x-table.th column="sub-account-name" :$sortAsc :$sortCol>{{ __('Sub Account Name') }}</x-table.th>
                    <x-table.th column="placement-date" :$sortAsc :$sortCol>{{ __('Placement Date') }}</x-table.th>
                    <x-table.th column="offer-type" :$sortAsc :$sortCol>{{ __('Offer Type') }}</x-table.th>
                    <x-table.th column="beg-balance" :$sortAsc :$sortCol>{{ __('Beg Balance') }}</x-table.th>
                    <x-table.th column="pay-off-balance" :$sortAsc :$sortCol>{{ __('Negotiated PayOff Balance') }}</x-table.th>
                    <x-table.th column="promise-amount" :$sortAsc :$sortCol>{{ __('Amount') }}</x-table.th>
                    <x-table.th column="promise-date" :$sortAsc :$sortCol>{{ __('Promise Date') }}</x-table.th>
                    <x-table.th>{{ __('Actions') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse($consumers as $consumer)
                    <x-table.tr>
                        <x-table.td>
                            <a
                                wire:navigate
                                class="hover:underline hover:underline-offset-4 text-primary"
                                href="{{ route('manage-consumers.view', ['consumer' => $consumer->id]) }}"
                            >
                                {{ str($consumer->first_name . ' ' . $consumer->last_name)->title() }}
                            </a>
                        </x-table.td>
                        <x-table.td>
                            <a
                                wire:navigate
                                class="hover:underline hover:underline-offset-4 text-primary"
                                href="{{ route('manage-consumers.view', ['consumer' => $consumer->id]) }}"
                            >
                                {{ $consumer->member_account_number }}
                            </a>
                        </x-table.td>
                        <x-table.td>{{ str($consumer->original_account_name)->title() }}</x-table.td>
                        <x-table.td>{{ $consumer->subclient_name ?? 'N/A' }}</x-table.td>
                        <x-table.td>
                            {{ $consumer->placement_date ? $consumer->placement_date->format('M d, Y') : 'N/A' }}
                        </x-table.td>
                        <x-table.td>
                            <span @class([
                                'badge bg-primary/20 text-primary whitespace-nowrap',
                                'bg-success/20 text-success' => $consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT,
                            ])>
                                {{ $consumer->consumerNegotiation->negotiation_type->displayOfferBadge() }}
                            </span>
                        </x-table.td>
                        <x-table.td>{{ Number::currency((float) $consumer->total_balance) }}</x-table.td>
                        @php
                            $consumerNegotiation = $consumer->consumerNegotiation;

                            [$promiseAmount, $promiseDate, $payOfBalance] = match (true) {
                                $consumerNegotiation->negotiation_type === NegotiationType::PIF && $consumerNegotiation->offer_accepted =>
                                    [
                                        $consumerNegotiation->one_time_settlement,
                                        $consumerNegotiation->first_pay_date,
                                        $consumerNegotiation->one_time_settlement,
                                    ],
                                $consumerNegotiation->negotiation_type === NegotiationType::PIF && $consumerNegotiation->counter_offer_accepted =>
                                    [
                                        $consumerNegotiation->counter_one_time_amount,
                                        $consumerNegotiation->counter_first_pay_date,
                                        $consumerNegotiation->counter_one_time_amount,
                                    ],
                                $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT && $consumerNegotiation->offer_accepted =>
                                    [
                                        $consumerNegotiation->monthly_amount,
                                        $consumerNegotiation->first_pay_date,
                                        $consumerNegotiation->negotiate_amount,
                                    ],
                                $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT && $consumerNegotiation->counter_offer_accepted =>
                                    [
                                        $consumerNegotiation->counter_monthly_amount,
                                        $consumerNegotiation->counter_first_pay_date,
                                        $consumerNegotiation->counter_negotiate_amount,
                                    ],
                                default => [null, null, null],
                            };
                        @endphp
                        <x-table.td> {{ Number::currency((float) $payOfBalance) }} </x-table.td>
                        <x-table.td> {{ Number::currency((float) $promiseAmount ?? 0) }} </x-table.td>
                        <x-table.td> {{ $promiseDate ? $promiseDate->format('M d, Y') : 'N/A' }} </x-table.td>
                        <x-table.td>
                            <x-consumer.menu>
                                <x-consumer.menu.button
                                    class="hover:bg-slate-100 p-1 rounded-full"
                                    x-on:close-menu.window="menuOpen = false"
                                >
                                    <x-heroicon-m-ellipsis-horizontal class="size-7" />
                                </x-consumer.menu.button>
                                <x-consumer.menu.items>
                                    <livewire:creditor.consumer-offers.view-offer
                                        :$consumer
                                        :isMenuItem="true"
                                        :isNotEditable="true"
                                        :key="str()->random(10)"
                                        lazy
                                    />

                                    <livewire:consumer.my-account.change-first-pay-date
                                        :$consumer
                                        :key="str()->random(10)"
                                    />

                                    <x-consumer.confirm-box
                                        :message="__('Do you want to restart this completed negotiation?')"
                                        :ok-button-label="__('Restart')"
                                        wire:click="restartNegotiation({{ $consumer->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="restartNegotiation({{ $consumer->id }})"
                                    >
                                        <x-consumer.menu.item>
                                            <span>{{ __('Start Over') }}</span>
                                        </x-consumer.menu.item>
                                    </x-consumer.confirm-box>

                                </x-consumer.menu.items>
                            </x-consumer.menu>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found :colspan="11" />
                @endforelse
            </x-slot>
        </x-table>
    </div>
    <x-table.per-page :items="$consumers" />
</x-dashboard.index-page>
