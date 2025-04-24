@use('Illuminate\Support\Number')
@use('App\Enums\TransactionStatus')
@use('App\Enums\TransactionType')
@use('App\Enums\Role')

<x-dashboard.index-page route-name="creditor.dashboard.recent-transactions">
    <div class="sm:flex items-center justify-between space-y-2 sm:space-y-0 sm:space-x-2 py-3 px-4">
        <h2 class="text-md text-black font-semibold lg:text-lg">
            {{ __('Recent Payments') }}
        </h2>
        <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-2 justify-end ">
            <x-table.per-page-count :items="$transactions" />
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                <x-search-box
                    name="search"
                    wire:model.live.debounce.400="search"
                    placeholder="{{ __('Search') }}"
                    :description="__('You can search by consumer name and account number')"
                />
            </div>

            @if ($transactions->isNotEmpty())
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
                    <x-table.th column="date" :$sortAsc :$sortCol>{{ __('Date') }}</x-table.th>
                    <x-table.th column="consumer_name" :$sortAsc :$sortCol>{{ __('Consumer Name') }}</x-table.th>
                    <x-table.th column="account_number" :$sortAsc :$sortCol>{{ __('Original Account #') }}</x-table.th>
                    <x-table.th column="transaction_type" :$sortAsc :$sortCol>{{ __('Type') }}</x-table.th>
                    <x-table.th column="amount" :$sortAsc :$sortCol>{{ __('Amount') }}</x-table.th>
                    <x-table.th column="subclient_name" :$sortAsc :$sortCol>{{ __('Sub Name') }}</x-table.th>
                    <x-table.th column="placement_date" :$sortAsc :$sortCol>{{ __('Placement Date') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse($transactions as $transaction)
                    <x-table.tr>
                        <x-table.td class="whitespace-nowrap">{{ $transaction->created_at->formatWithTimezone(format: 'M d, Y h:i A') }}</x-table.td>
                        <x-table.td>
                            <a
                                wire:navigate
                                class="hover:underline hover:underline-offset-4 text-primary whitespace-nowrap"
                                href="{{ route('manage-consumers.view', ['consumer' => $transaction->consumer->id]) }}"
                            >
                                {{ $transaction->consumer->first_name . ' ' . $transaction->consumer->last_name }}
                            </a>
                        </x-table.td>
                        <x-table.td>
                            <a
                                wire:navigate
                                href="{{ route('manage-consumers.view', ['consumer' => $transaction->consumer_id]) }}"
                                class="text-primary hover:cursor-pointer hover:underline underline-offset-2"
                            >
                                {{ $transaction->consumer->member_account_number }}</x-table.td>
                            </a>
                        <x-table.td>
                            <span @class([
                                'badge bg-primary/20 text-primary whitespace-nowrap',
                                'bg-success/20 text-success' => $transaction->transaction_type === TransactionType::PIF,
                                'bg-secondary/20 text-secondary' => $transaction->transaction_type === TransactionType::INSTALLMENT,
                            ])>
                                {{ $transaction->transaction_type->displayOfferBadge() }}
                            </span>
                        </x-table.td>
                        <x-table.td>{{ Number::currency((float) $transaction->amount) }}</x-table.td>
                        <x-table.td>{{ $transaction->consumer->subclient_name ?? 'N/A' }}</x-table.td>
                        <x-table.td>{{ $transaction->consumer->placement_date?->formatWithTimezone() ?? 'N/A' }}</x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found :colspan="6" />
                @endforelse
            </x-slot>
        </x-table>
    </div>
    <x-table.per-page :items="$transactions" />
</x-dashboard.index-page>
