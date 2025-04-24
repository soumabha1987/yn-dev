@use('App\Enums\Role')

<x-dashboard.index-page route-name="creditor.dashboard.dispute-reports">
    <div class="sm:flex items-center justify-between space-y-2 sm:space-y-0 sm:space-x-2 py-3 px-4">
        <h2 class="text-md text-black font-semibold lg:text-lg">
            {{ __('Disputes/No Pay') }}
        </h2>
        <div class="sm:flex flex-col sm:flex-row items-start sm:items-center sm:space-x-2 space-y-2 sm:space-y-0">
            <x-table.per-page-count :items="$consumers" />
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                <x-search-box
                    name="search"
                    wire:model.live.debounce.400="search"
                    placeholder="{{ __('Search') }}"
                    :description="__('You can search by its consumer name')"
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
                    <x-table.th column="date" :$sortAsc :$sortCol>{{ __('Date/Time') }}</x-table.th>
                    <x-table.th column="account_balance" :$sortAsc :$sortCol>{{ __('Account Balance') }}</x-table.th>
                    <x-table.th column="consumer_name" :$sortAsc :$sortCol>{{ __('Consumer Name') }}</x-table.th>
                    <x-table.th column="account_number" :$sortAsc :$sortCol>{{ __('Account #') }}</x-table.th>
                    <x-table.th column="account_name" :$sortAsc :$sortCol>{{ __('Account Name') }}</x-table.th>
                    <x-table.th column="sub_account_name" :$sortAsc :$sortCol>{{ __('Sub Account Name') }}</x-table.th>
                    <x-table.th column="placement_date" :$sortAsc :$sortCol>{{ __('Placement Date') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse ($consumers as $consumer)
                    <x-table.tr>
                        <x-table.td>{{ $consumer->disputed_at->formatWithTimezone(format: 'M d, Y h:i A') }}</x-table.td>
                        <x-table.td>{{ Number::currency((float) $consumer->current_balance) }}</x-table.td>
                        <x-table.td>
                            <a
                                wire:navigate
                                class="hover:underline hover:underline-offset-4 text-primary"
                                href="{{ route('manage-consumers.view', ['consumer' => $consumer->id]) }}"
                            >
                                {{ $consumer->first_name . ' ' . $consumer->last_name }}
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
                        <x-table.td>{{ $consumer->original_account_name }}</x-table.td>
                        <x-table.td>{{ $consumer->subclient_name ?? 'N/A' }}</x-table.td>
                        <x-table.td>{{ $consumer->placement_date?->format('M d, Y') ?? 'N/A' }}</x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found :colspan="7" />
                @endforelse
            </x-slot>
        </x-table>
    </div>
    <x-table.per-page :items="$consumers" />
</x-dashboard.index-page>
