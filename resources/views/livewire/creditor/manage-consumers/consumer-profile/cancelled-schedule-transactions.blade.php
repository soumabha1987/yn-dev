@use('Illuminate\Support\Number')
@use('App\Enums\Role')

<div x-on:refresh-please.window="$wire.$refresh">
    <div>
        <div
            @class([
                'flex flex-wrap sm:flex-nowrap p-4 sm:items-center gap-4',
                'justify-between' => $cancelledScheduleTransactions->isNotEmpty(),
                'justify-start' => $cancelledScheduleTransactions->isEmpty()
            ])
        >
            <h2 class="text-black tracking-wide font-semibold text-lg">
                <span>{{ __('Schedule Cancelled Payment Details') }}</span>
            </h2>
            <x-table.per-page-count :items="$cancelledScheduleTransactions" />
        </div>

        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th>{{ __('Schedule Date') }}</x-table.th>
                        <x-table.th>{{ __('Transaction Amount') }}</x-table.th>
                        <x-table.th>{{ __('Payment Method') }}</x-table.th>
                        @role(Role::SUPERADMIN)
                            <x-table.th>{{ __('Company Name') }}</x-table.th>
                        @endrole
                        @hasanyrole([Role::SUPERADMIN, Role::CREDITOR])
                            <x-table.th>{{ __('Subclient Name') }}</x-table.th>
                        @endhasanyrole
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($cancelledScheduleTransactions as $cancelledScheduleTransaction)
                        <x-table.tr>
                            <x-table.td>{{ $cancelledScheduleTransaction->schedule_date->format('M d, Y') }}</x-table.td>
                            <x-table.td>{{ Number::currency((float) $cancelledScheduleTransaction->amount ?? 0) }}</x-table.td>
                            <x-table.td>{{ $cancelledScheduleTransaction->paymentProfile->method->displayName() }}</x-table.td>
                            @role(Role::SUPERADMIN)
                                <x-table.td>{{ $cancelledScheduleTransaction->company?->company_name ?? 'N/A' }}</x-table.td>
                            @endrole
                            @hasanyrole([Role::SUPERADMIN, Role::CREDITOR])
                                <x-table.td>{{ $cancelledScheduleTransaction->subclient?->subclient_name ?? 'N/A' }}</x-table.td>
                            @endhasanyrole
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="5" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$cancelledScheduleTransactions" />
    </div>
</div>
