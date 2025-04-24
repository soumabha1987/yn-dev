@use('Illuminate\Support\Number')

<div>
    <div class="card">
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $scheduleTransactions->isNotEmpty(),
                'justify-end' => $scheduleTransactions->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$scheduleTransactions" />
            <x-search-box
                name="search"
                wire:model.live.debounce.400="search"
                placeholder="{{ __('Search') }}"
                :description="__('You can search by its name and account number.')"
            />
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="date_time" :$sortCol :$sortAsc>{{ __('Date/Time') }}</x-table.th>
                        <x-table.th column="consumer_name" :$sortCol :$sortAsc>{{ __('Consumer Name') }}</x-table.th>
                        <x-table.th column="member_account_number" :$sortCol :$sortAsc>{{ __('Account Number') }}</x-table.th>
                        <x-table.th>{{ __('Sub Account(s)') }}</x-table.th>
                        <x-table.th column="transaction_amount" :$sortCol :$sortAsc>{{ __('Transaction Amount') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($scheduleTransactions as $scheduleTransaction)
                        <x-table.tr>
                            <x-table.td>{{ $scheduleTransaction->schedule_date->format('M d, Y') }}</x-table.td>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    class="hover:underline hover:underline-offset-4 text-primary"
                                    href="{{ route('manage-consumers.view', $scheduleTransaction->consumer_id) }}"
                                >
                                    {{ $scheduleTransaction->consumer->first_name . ' ' . $scheduleTransaction->consumer->last_name }}
                                </a>
                            </x-table.td>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    class="hover:underline hover:underline-offset-4 text-primary"
                                    href="{{ route('manage-consumers.view', $scheduleTransaction->consumer_id) }}"
                                >
                                    {{ $scheduleTransaction->consumer->member_account_number }}
                                </a>
                            </x-table.td>
                            <x-table.td>
                                {{ $scheduleTransaction->consumer->subclient_name ?? 'N/A' }}
                            </x-table.td>
                            <x-table.td>{{ Number::currency((float) $scheduleTransaction->amount) }}</x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="5" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$scheduleTransactions" />
    </div>
</div>
