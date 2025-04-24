@use('Illuminate\Support\Number')
@use('App\Enums\Role')

<x-dashboard.index-page route-name="creditor.dashboard.failed-payments">
    <div class="sm:flex items-center justify-between space-y-2 sm:space-y-0 sm:space-x-2 py-3 px-4">
        <h2 class="text-md text-black font-semibold lg:text-lg">
            {{ __('Failed Payments') }}
        </h2>

        <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
            <x-table.per-page-count :items="$scheduleTransactions" />
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                <x-search-box
                    name="search"
                    wire:model.live.debounce.400="search"
                    :placeholder="__('Search')"
                    :description="__('You can search by consumer name and account number')"
                />
            </div>

            @if ($scheduleTransactions->isNotEmpty())
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
                    <x-table.th column="due_date" :$sortAsc :$sortCol>{{ __('Due Date') }}</x-table.th>
                    <x-table.th column="last_failed_date" :$sortAsc :$sortCol>{{ __('Last Failed Date') }}</x-table.th>
                    <x-table.th column="account_number" :$sortAsc :$sortCol>{{ __('Account #') }}</x-table.th>
                    <x-table.th column="consumer_name" :$sortAsc :$sortCol>{{ __('Consumer Name') }}</x-table.th>
                    <x-table.th column="account_name" :$sortAsc :$sortCol>{{ __('Account Name') }}</x-table.th>
                    <x-table.th column="sub_account_name" :$sortAsc :$sortCol>{{ __('Sub Account Name') }}</x-table.th>
                    <x-table.th column="placement_date" :$sortAsc :$sortCol>{{ __('Placement Date') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse ($scheduleTransactions as $scheduleTransaction)
                    <x-table.tr>
                        <x-table.td>{{ $scheduleTransaction->schedule_date->format('M d, Y') }}</x-table.td>
                        <x-table.td>{{ $scheduleTransaction->last_attempted_at->formatWithTimezone() }}</x-table.td>
                        <x-table.td>
                            <a
                                wire:navigate
                                href="{{ route('manage-consumers.view', ['consumer' => $scheduleTransaction->consumer_id]) }}"
                                class="hover:underline text-primary"
                            >
                                {{ $scheduleTransaction->consumer->member_account_number }}
                            </a>
                        </x-table.td>
                        <x-table.td>
                            <a
                                wire:navigate
                                href="{{ route('manage-consumers.view', ['consumer' => $scheduleTransaction->consumer_id]) }}"
                                class="hover:underline text-primary"
                            >
                                {{ str($scheduleTransaction->consumer->first_name . ' ' . $scheduleTransaction->consumer->last_name)->title() }}
                            </a>
                        </x-table.td>
                        <x-table.td>{{ $scheduleTransaction->consumer->original_account_name ?? 'N/A' }}</x-table.td>
                        <x-table.td>{{ $scheduleTransaction->consumer->subclient_name ?? 'N/A' }}</x-table.td>
                        <x-table.td>{{ $scheduleTransaction->consumer->placement_date?->format('M d, Y') ?? 'N/A' }}</x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found :colspan="7" />
                @endforelse
            </x-slot>
        </x-table>
    </div>
    <x-table.per-page :items="$scheduleTransactions" />
</x-dashboard.index-page>
