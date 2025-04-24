@use('Illuminate\Support\Number')
@use('App\Enums\TransactionStatus')
@use('App\Enums\TransactionType')
@use('App\Enums\Role')
@use('App\Enums\ConsumerStatus')

<div>
    <div>
        <div
            @class([
                'flex flex-wrap sm:flex-nowrap p-4 sm:items-center gap-4',
                'justify-between' => $transactions->isNotEmpty(),
                'justify-start' => $transactions->isEmpty()
            ])
        >
            <h2 class="text-black tracking-wide font-semibold text-lg">
                <span>{{ __('Transaction History') }}</span>
            </h2>
            <x-table.per-page-count :items="$transactions" />
        </div>

        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th>{{ __('Date/Time') }}</x-table.th>
                        <x-table.th>{{ __('Consumer Name') }}</x-table.th>
                        @role(Role::SUPERADMIN)
                            <x-table.th>{{ __('Company Name') }}</x-table.th>
                        @endrole
                        <x-table.th>{{ __('Transaction ID') }}</x-table.th>
                        <x-table.th>{{ __('Transaction Amount') }}</x-table.th>
                        <x-table.th>{{ __('Failed Reason') }}</x-table.th>
                        <x-table.th>{{ __('Status') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($transactions as $transaction)
                        <x-table.tr wire:key="{{ str()->random(10) }}">
                            <x-table.td>{{ $transaction->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td>{{ $transaction->consumer->first_name . ' ' . $transaction->consumer->last_name }}</x-table.td>
                            @role(Role::SUPERADMIN)
                                <x-table.td>{{ $transaction->company?->company_name ?? 'N/A' }}</x-table.td>
                            @endrole
                            <x-table.td>{{ $transaction->transaction_id ?? 'N/A' }}</x-table.td>
                            <x-table.td>{{ Number::currency((float) $transaction->amount ?? 0) }}</x-table.td>
                            <x-table.td>
                                @if(
                                    (! blank($transaction->gateway_response['Error'] ?? []))
                                    && $transaction->status === TransactionStatus::FAILED
                                )
                                    @json($transaction->gateway_response['Error'])
                                @else
                                    {{ 'N/A' }}
                                @endif
                            </x-table.td>
                            <x-table.td>
                                <div class="flex items-center gap-x-2">
                                    @if ($transaction->external_payment_profile_id)
                                        <span class="badge p-2 rounded-md bg-secondary/20 text-secondary text-nowrap">
                                            {{ __('Paid By Helping Hand') }}
                                        </span>
                                    @else
                                        <span
                                            class="badge p-2 rounded-md"
                                            x-bind:class="{
                                                'bg-success/20 text-success' :  @js($transaction->status === TransactionStatus::SUCCESSFUL),
                                                'bg-error/20 text-error' :  @js($transaction->status === TransactionStatus::FAILED),
                                            }"
                                        >
                                            {{ $transaction->status->displayName() }}
                                        </span>
                                    @endif
                                    @if (
                                        $transaction->status === TransactionStatus::FAILED
                                        && $transaction->scheduleTransaction
                                        && $transaction->scheduleTransaction->status !== TransactionStatus::RESCHEDULED
                                        && $consumer->status !== ConsumerStatus::SETTLED
                                    )
                                        <x-dialog>
                                            <x-dialog.open>
                                                <button
                                                    type="button"
                                                    class="btn select-none text-white bg-primary hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                                                >
                                                    {{ __('Reschedule') }}
                                                </button>
                                            </x-dialog.open>

                                            <div
                                                x-data="reschedule"
                                                x-on:close-dialog.window="dialogOpen = false"
                                            >
                                                <x-dialog.panel :needDialogPanel="false">
                                                    <x-slot name="heading">{{__('Reschedule Date')}}</x-slot>
                                                    <form
                                                        wire:submit="reschedule({{ $transaction->id }})"
                                                        method="POST"
                                                        autocomplete="off"
                                                    >
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
                                                                required
                                                                autocomplete="off"
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
                                    @elseif(
                                        $transaction->scheduleTransaction
                                        && $transaction->scheduleTransaction?->status === TransactionStatus::RESCHEDULED
                                    )
                                        <span class="badge p-2 rounded-md bg-info/20 text-info">
                                            {{ TransactionStatus::RESCHEDULED->displayName() }}
                                        </span>
                                    @endif
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="7" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$transactions" />
    </div>
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
</div>
