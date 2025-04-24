@use('Illuminate\Support\Number')
@use('App\Enums\TransactionType')
@use('App\Enums\Role')

<x-dashboard.index-page route-name="creditor.dashboard.upcoming-transactions">
    <div class="sm:flex items-center justify-between space-y-2 sm:space-y-0 sm:space-x-2 py-3 px-4">
        <h2 class="text-md text-black font-semibold lg:text-lg">
            {{ __('Upcoming Payments') }}
        </h2>

        <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-2 justify-end">
            <x-table.per-page-count :items="$transactions" />
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                <x-search-box
                    name="search"
                    wire:model.live.debounce.400="search"
                    placeholder="{{ __('Search') }}"
                    :description="__('You can search by consumer name, account number, and also schedule date using the same format')"
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
    <div class="min-w-full overflow-auto">
        <x-table>
            <x-slot name="tableHead">
                <x-table.tr>
                    <x-table.th column="schedule_date" :$sortAsc :$sortCol>{{ __('Schedule Date') }}</x-table.th>
                    <x-table.th column="amount" :$sortAsc :$sortCol>{{ __('Scheduled Amount') }}</x-table.th>
                    <x-table.th column="pay_type" :$sortAsc :$sortCol>{{ __('Pay Type') }}</x-table.th>
                    <x-table.th column="consumer_name" :$sortAsc :$sortCol>{{ __('Consumer Name') }}</x-table.th>
                    <x-table.th column="account_number" :$sortAsc :$sortCol>{{ __('Account #') }}</x-table.th>
                    <x-table.th column="account_name" :$sortAsc :$sortCol>{{ __('Account Name') }}</x-table.th>
                    <x-table.th column="sub_name" :$sortAsc :$sortCol>{{ __('Sub Account Name') }}</x-table.th>
                    <x-table.th column="placement_date" :$sortAsc :$sortCol>{{ __('Placement Date') }}</x-table.th>
                    <x-table.th>{{ __('Actions') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse ($transactions as $transaction)
                    <x-table.tr wire:key="{{ str()->random(10) }}">
                        <x-table.td>{{ $transaction->schedule_date->format('M d, Y') }}</x-table.td>
                        <x-table.td>{{ Number::currency((float) ($transaction->amount ?? 0)) }}</x-table.td>
                        <x-table.td>
                            {{ $transaction->transaction_type === TransactionType::PIF ? __('Settle') : __('Pay Plan') }}
                        </x-table.td>
                        <x-table.td>
                            <a
                                wire:navigate
                                class="hover:underline hover:underline-offset-4 text-primary whitespace-nowrap"
                                href="{{ route('manage-consumers.view', ['consumer' => $transaction->consumer_id]) }}"
                            >
                                {{ $transaction->consumer->first_name . ' ' . $transaction->consumer->last_name }}
                            </a>
                        </x-table.td>
                        <x-table.td>
                            <a
                                wire:navigate
                                class="hover:underline hover:underline-offset-4 text-primary whitespace-nowrap"
                                href="{{ route('manage-consumers.view', ['consumer' => $transaction->consumer_id]) }}"
                            >
                                {{ $transaction->consumer->member_account_number }}
                            </a>
                        </x-table.td>
                        <x-table.td>{{ $transaction->consumer->original_account_name }}</x-table.td>
                        <x-table.td>
                            {{ $transaction->consumer->subclient_name ?? 'N/A' }}
                        </x-table.td>
                        <x-table.td>
                            {{ $transaction->consumer->placement_date?->formatWithTimezone() ?? 'N/A' }}
                        </x-table.td>
                        <x-table.td>
                            <div class="flex flex-col gap-1">
                                <x-dialog>
                                    <x-dialog.open>
                                        <x-form.button
                                            type="button"
                                            variant="primary"
                                            class="py-1.5 px-3 text-xs sm:text-sm+"
                                        >
                                            <x-lucide-refresh-cw class="size-4.5 sm:size-5 mr-1"/>
                                            {{ __('Reschedule') }}
                                        </x-form.button>
                                    </x-dialog.open>

                                    <div
                                        x-data="reschedule"
                                        @close-dialog.window="dialogOpen = false"
                                    >
                                        <x-dialog.panel :needDialogPanel="false">
                                            <x-slot name="heading">{{__('Reschedule Date')}}</x-slot>
                                            <form wire:submit="reschedule({{ $transaction->id }})" method="POST" autocomplete="off">
                                                <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                                                    {{ __('Reschedule Date') }}<span class="text-error text-base leading-none">*</span>
                                                </span>
                                                <div wire:ignore>
                                                    <input
                                                        wire:model="schedule_date"
                                                        type="text"
                                                        name="schedule_date"
                                                        x-init="flatPickr(@js($transaction->schedule_date))"
                                                        class="form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary w-full"
                                                    />
                                                </div>
                                                @error('schedule_date')
                                                    <div class="mt-2">
                                                        <span class="text-error text-sm+">
                                                            {{ $message }}
                                                        </span>
                                                    </div>
                                                @enderror
                                                <div class="space-x-2 text-right">
                                                    <x-dialog.close>
                                                        <x-form.default-button type="button">
                                                            {{ __('Cancel') }}
                                                        </x-form.default-button>
                                                    </x-dialog.close>
                                                    <x-form.button
                                                        type="submit"
                                                        variant="primary"
                                                        class="mt-4 border focus:border-primary-focus"
                                                    >
                                                        {{ __('Submit') }}
                                                    </x-form.button>
                                                </div>
                                            </form>
                                        </x-dialog.panel>
                                    </div>
                                </x-dialog>
                            </div>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found :colspan="9" />
                @endforelse
            </x-slot>
        </x-table>
    </div>
    <x-table.per-page :items="$transactions" />
    @script
        <script>
            Alpine.data('reschedule', () => ({
                flatPickrInstance: null,
                flatPickr (defaultDate) {

                    this.flatPickrInstance = window.flatpickr(this.$el, {
                        altInput: true,
                        altFormat: 'm/d/Y',
                        allowInput: true,
                        dateFormat: 'Y-m-d',
                        allowInvalidPreload: true,
                        disableMobile: true,
                        ariaDateFormat: 'm/d/Y',
                        defaultDate,
                        minDate: @js(today()->toDateString()),
                    })
                },
                destroy() {
                    this.flatPickrInstance?.destroy()
                }
            }))
        </script>
    @endscript
</x-dashboard.index-page>
