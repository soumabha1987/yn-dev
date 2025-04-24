@use('Illuminate\Support\Number')
@use('App\Enums\Role')
@use('App\Enums\TransactionStatus')
@use('App\Enums\ConsumerStatus')

<div>
    <div x-on:refresh-please.window="$wire.$refresh">
        <div
            @class([
                'flex flex-wrap sm:flex-nowrap p-4 sm:items-center gap-4',
                'justify-between' => $scheduledTransactions->isNotEmpty(),
                'justify-start' => $scheduledTransactions->isEmpty()
            ])
        >
            <h2 class="text-black tracking-wide font-semibold text-lg">
                <span>{{ __('Payment Plans') }}</span>
            </h2>
            <x-table.per-page-count :items="$scheduledTransactions" />
        </div>
        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th>{{ __('Date/Time') }}</x-table.th>
                        <x-table.th>{{ __('Transaction Amount') }}</x-table.th>
                        @role(Role::SUPERADMIN)
                            <x-table.th>{{ __('Company Name') }}</x-table.th>
                        @endrole
                        <x-table.th>{{ __('Status') }}</x-table.th>
                        <x-table.th>{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($scheduledTransactions as $scheduledTransaction)
                        <x-table.tr wire:key="{{ str()->random(10) }}">
                            <x-table.td>
                                {{ $scheduledTransaction->schedule_date->format('M d, Y') }}
                                @if ($previousTransactionDate = $scheduledTransaction->previous_schedule_date)
                                    <p class="text-success text-xs">
                                        {{ __('Originally scheduled on: ') . $previousTransactionDate->format('M d, Y') }}
                                    </p>
                                @endif
                            </x-table.td>
                            <x-table.td>{{ Number::currency((float) $scheduledTransaction->amount ?? 0) }}</x-table.td>
                            @role(Role::SUPERADMIN)
                                <x-table.td>{{ $scheduledTransaction->company?->company_name ?? 'N/A' }}</x-table.td>
                            @endrole
                            <x-table.td>
                                <span
                                     @class([
                                        'badge p-2 rounded-md whitespace-nowrap',
                                        'bg-error/10 text-error' => $scheduledTransaction->status === TransactionStatus::FAILED || $consumer->status === ConsumerStatus::HOLD,
                                        'bg-primary/10 text-primary' => $scheduledTransaction->status === TransactionStatus::SCHEDULED && $consumer->status !== ConsumerStatus::HOLD,
                                    ])
                                >
                                    @if ($consumer->status === ConsumerStatus::HOLD)
                                        <span>{{ __('Consumer Hold') }}</span>
                                    @else
                                        {{ $scheduledTransaction->status->displayName() }}
                                    @endif
                                </span>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex items-center space-x-2">
                                    @if (
                                        in_array($scheduledTransaction->status, [TransactionStatus::FAILED, TransactionStatus::SCHEDULED])
                                        && ! in_array($consumer->status, [ConsumerStatus::SETTLED, ConsumerStatus::DEACTIVATED])
                                    )
                                        <x-dialog>
                                            <x-dialog.open>
                                                <x-form.button
                                                    type="button"
                                                    variant="primary"
                                                    class="text-xs sm:text-sm+ border border-primary py-1.5 px-3 text-nowrap"
                                                >
                                                    {{ __('Change Date') }}
                                                </x-form.button>
                                            </x-dialog.open>

                                            <div
                                                x-data="reschedule"
                                                @close-dialog.window="dialogOpen = false"
                                            >
                                                <x-dialog.panel :needDialogPanel="false">
                                                    <x-slot name="heading">{{__('Change Date')}}</x-slot>
                                                    <form wire:submit="reschedule({{ $scheduledTransaction->id }})" method="POST" autocomplete="off">
                                                        <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                                                            {{ __('Reschedule Date') }}<span class="text-error text-base leading-none">*</span>
                                                        </span>
                                                        <div wire:ignore>
                                                            <input
                                                                wire:model="schedule_date"
                                                                type="text"
                                                                name="schedule_date"
                                                                x-init="flatPickr(@js($scheduledTransaction->schedule_date))"
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
                                        <x-form.default-button
                                            class="text-xs sm:text-sm+ py-1.5 px-3 font-normal"
                                            type="button"
                                            wire:click="cancelScheduled({{ $scheduledTransaction->id }})"
                                        >
                                            <span>{{ __('Cancel') }}</span>
                                        </x-form.default-button>

                                        @if (! $loop->last && $loop->first && $consumer->status !== ConsumerStatus::SETTLED)
                                            <x-dialog
                                                @close-confirmation-box.window="dialogOpen = false"
                                            >
                                                <x-dialog.open>
                                                    <x-form.button
                                                        class="text-xs sm:text-sm+ border border-error py-1.5 px-3 text-nowrap"
                                                        type="button"
                                                        variant="error"
                                                    >
                                                        <span>{{__('Skip')}}</span>
                                                    </x-form.button>
                                                </x-dialog.open>
                                                <x-dialog.panel
                                                    confirm-box
                                                    size="2xl"
                                                    :need-dialog-panel="false"
                                                >
                                                    <x-slot name="heading">
                                                        <span class="text-lg sm:text-xl font-semibold text-black">
                                                            {{ __('Understanding Skip a Payment') }}
                                                        </span>
                                                    </x-slot>

                                                    <x-slot name="svg">
                                                        <div class="flex items-center justify-center">
                                                            <span class="text-4xl lg:text-8xl">🤓</span>
                                                        </div>
                                                    </x-slot>

                                                    <x-slot name="buttons">
                                                        <div class="flex flex-col sm:flex-row justify-center items-stretch sm:items-center gap-2">
                                                            <x-form.button
                                                                type="button"
                                                                variant="error"
                                                                class="border focus:border-error-focus w-full sm:w-auto"
                                                                wire:click="skipPayment({{ $scheduledTransaction->id }})"
                                                                wire:target="skipPayment({{ $scheduledTransaction->id }})"
                                                                wire:loading.attr="disabled"
                                                            >
                                                                <div
                                                                    wire:loading.flex
                                                                    wire:target="skipPayment({{ $scheduledTransaction->id }})"
                                                                    class="flex items-center gap-x-2"
                                                                >
                                                                    <x-lucide-loader-2 class="size-5 animate-spin" />
                                                                    <span>{{ __('Skipping...') }}</span>
                                                                </div>
                                                                <span wire:loading.remove>{{ __('Skip This Payment') }}</span>
                                                            </x-form.button>

                                                            <x-dialog.close>
                                                                <x-form.default-button
                                                                    type="button"
                                                                    class="w-full sm:w-auto"
                                                                >
                                                                    {{ __('Cancel') }}
                                                                </x-form.default-button>
                                                            </x-dialog.close>
                                                        </div>
                                                    </x-slot>

                                                    <x-slot name="message">
                                                        <div class="text-sm space-y-3 mx-4 text-black text-start max-h-[40vh] sm:max-h-[20vh] md:max-h-[28vh] lg:max-h-full overflow-y-auto scroll-bar-visible">
                                                            <p>{{ __("When a payment is skipped, it moves to the back of the payment schedule and the next payment is still due on the scheduled due date (unless the date is changed).") }}</p>
                                                            <p>{{ __('YouNegotiate will never turn off the payment plan so you can jump back in when you are able.') }}</p>
                                                            <p>{{ __("Don't forget! Consumers have a tax deductible helping hand link so anyone can make payments on their behalf and get a tax write off!! Debt Free gifting is the coolest new gift ever!!") }}</p>
                                                        </div>
                                                    </x-slot>
                                                </x-dialog.panel>
                                            </x-dialog>

                                        @endif
                                    @else
                                        -
                                    @endif
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="5" />
                    @endforelse
                </x-slot>
            </x-table>
            <x-loader wire:loading />
        </div>
        <x-table.per-page :items="$scheduledTransactions" />
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
