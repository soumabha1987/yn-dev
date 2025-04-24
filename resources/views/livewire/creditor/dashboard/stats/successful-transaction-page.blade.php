@use('Illuminate\Support\Number')

<div>
    <div class="card">
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $transactions->isNotEmpty(),
                'justify-end' => $transactions->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$transactions" />
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
                        <x-table.th column="date_time" :$sortAsc :$sortCol>{{ __('Date/Time') }}</x-table.th>
                        <x-table.th column="member_account_number" :$sortAsc :$sortCol>{{ __('Account Number') }}</x-table.th>
                        <x-table.th column="consumer_name" :$sortAsc :$sortCol>{{ __('Consumer Name') }}</x-table.th>
                        <x-table.th>{{ __('Sub Account(s)') }}</x-table.th>
                        <x-table.th>{{ __('Transaction ID') }}</x-table.th>
                        <x-table.th column="amount" :$sortAsc :$sortCol>{{ __('Transaction Amount') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($transactions as $transaction)
                        <x-table.tr>
                            <x-table.td>{{ $transaction->created_at->formatWithTimezone(format: 'M d, Y h:i A') }}</x-table.td>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    class="hover:underline hover:underline-offset-4 text-primary"
                                    href="{{ route('manage-consumers.view', $transaction->consumer_id) }}"
                                >
                                    {{ $transaction->consumer->member_account_number }}
                                </a>
                            </x-table.td>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    class="hover:underline hover:underline-offset-4 text-primary"
                                    href="{{ route('manage-consumers.view', $transaction->consumer_id) }}"
                                >
                                    {{ $transaction->consumer->first_name . ' ' . $transaction->consumer->last_name }}
                                </a>
                            </x-table.td>
                            <x-table.td>
                                {{ $transaction->consumer->subclient_name ?? 'N/A' }}
                            </x-table.td>
                            <x-table.td>{{ $transaction->transaction_id }}</x-table.td>
                            <x-table.td>{{ Number::currency((float) ($transaction->amount ?? 0)) }}</x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="6" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$transactions" />
    </div>
</div>
