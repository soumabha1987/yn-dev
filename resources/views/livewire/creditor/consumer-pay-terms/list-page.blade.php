@use('Illuminate\Support\Number')

<div>
    <div
        class="card"
        x-on:refresh-parent.window="$wire.$refresh"
    >
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $consumers->isNotEmpty(),
                'justify-end' => $consumers->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$consumers" />
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto gap-3">
                <x-search-box
                    name="search"
                    wire:model.live.debounce.400="search"
                    placeholder="{{ __('Search') }}"
                    :description="__('You can search by consumer name and account number')"
                />
                @if ($consumers->isNotEmpty())
                    <div>
                        <x-form.button
                            wire:click="export"
                            wire:target="export"
                            wire:loading.attr="disabled"
                            type="button"
                            variant="primary"
                            class="flex items-center space-x-2 disabled:opactiy-50"
                        >
                            <span>{{ __('Export') }}</span>
                            <x-lucide-download class="size-5" wire:loading.remove wire:target="export" />
                            <x-lucide-loader-2 class="animate-spin size-5" wire:loading wire:target="export" />
                        </x-form.button>
                    </div>
                @endif
            </div>
        </div>
        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="member-account-number" :$sortCol :$sortAsc>{{ __('Member Account #') }}</x-table.th>
                        <x-table.th column="consumer-name" :$sortCol :$sortAsc>{{ __('Consumer Name') }}</x-table.th>
                        <x-table.th column="sub-name" :$sortCol :$sortAsc>{{ __('Sub Name/ID') }}</x-table.th>
                        <x-table.th column="current-balance" :$sortCol :$sortAsc>{{ __('Current Balance') }}</x-table.th>
                        <x-table.th column="settlement-offer" :$sortCol :$sortAsc>{{ __('Settlement Offer') }}</x-table.th>
                        <x-table.th column="plan-balance-offer" :$sortCol :$sortAsc>{{ __('Plan Balance Offer') }}</x-table.th>
                        <x-table.th column="min-monthly-payment" :$sortCol :$sortAsc>{{ __('Min Monthly Payment') }}</x-table.th>
                        <x-table.th column="max-days-first-pay" :$sortCol :$sortAsc>{{ __('Days/1st Payment') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse($consumers as $consumer)
                        <x-table.tr>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    href="{{ route('manage-consumers.view', ['consumer' => $consumer->id]) }}"
                                    class="hover:underline text-primary"
                                >
                                    {{ $consumer->member_account_number ?? 'N/A' }}
                                </a>
                            </x-table.td>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    href="{{ route('manage-consumers.view', ['consumer' => $consumer->id]) }}"
                                    class="hover:underline text-primary"
                                >
                                    {{ str($consumer->first_name . ' ' .$consumer->last_name)->title() }}
                                </a>
                            </x-table.td>
                            <x-table.td>
                                {{ $consumer->subclient_name ? str($consumer->subclient_name. '/' .$consumer->subclient_account_number)->title() : 'N/A' }}
                            </x-table.td>
                            <x-table.td> {{ Number::currency((float) $consumer->total_balance ?? 0) }} </x-table.td>
                            <x-table.td>
                                {{ $consumer->pif_discount_percent ? Number::percentage($consumer->pif_discount_percent, 2) : 'N/A' }}
                            </x-table.td>
                            <x-table.td>
                                {{ $consumer->pay_setup_discount_percent ? Number::percentage($consumer->pay_setup_discount_percent, 2) : 'N/A' }}
                            </x-table.td>
                            <x-table.td>
                                @php
                                    $amount = null;
                                    if ($consumer->pay_setup_discount_percent && $consumer->min_monthly_pay_percent) {
                                        $discountBalance = $consumer->total_balance - ($consumer->total_balance * $consumer->pay_setup_discount_percent / 100);
                                        $amount = $discountBalance * $consumer->min_monthly_pay_percent / 100;
                                    }
                                @endphp
                                {{ $amount ? Number::currency((float) $amount) : 'N/A' }}
                            </x-table.td>
                            <x-table.td>
                                {{ $consumer->max_days_first_pay ?? 'N/A' }}
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="9" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$consumers" />
    </div>
</div>
