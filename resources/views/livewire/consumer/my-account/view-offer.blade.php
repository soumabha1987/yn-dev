@use('Illuminate\Support\Number')
@use('App\Enums\InstallmentType')
@use('App\Enums\NegotiationType')

<div>
    <div x-data="{ sendCounterOffer: false }">
        <x-consumer.dialog>
            <x-consumer.dialog.open>
                @if ($view === 'card')
                    <button
                        type="button"
                        class="btn space-x-2 w-full px-0 font-semibold sm:text-lg text-purple-600 hover:outline-2 hover:outline-purple-600 outline outline-[#2F3C4633] focus:outline-[#2F3C4633]"
                    >
                        {{ __('Open Counter Offer') }}
                    </button>
                @elseif ($view === 'grid')
                    <x-form.button
                        type="button"
                        variant="success"
                        class="text-nowrap text-sm space-x-2 w-full p-2 font-medium"
                    >
                        {{ __('Open Counter Offer') }}
                    </x-form.button>
                @elseif ($view === 'tab')
                    <span class="tag cursor-pointer text-sm rounded-full px-4 xl:text-nowrap {{ $tabClasses }}">
                        {{ $tabLabel }}
                    </span>
                @endif
            </x-consumer.dialog.open>

            <x-consumer.dialog.panel
                :need-dialog-panel="false"
                size="xl"
            >
                <x-slot name="heading">
                    <span x-text="sendCounterOffer ? @js(__('Counter Offer')) : @js(__('Offer Details'))" />
                </x-slot>


                @if ($consumer->offerDetails['account_profile_details'] ?? false)
                    <div class="flex items-center justify-between p-3 border border-primary-400 rounded-lg bg-primary-light text-black flex-wrap">
                        <div>
                            <h2 class="text-xl capitalize font-bold">{{ $consumer->offerDetails['account_profile_details']['creditor_name'] }}</h2>
                            <p class="text-xs+ sm:text-sm text-gray-700">{{ $consumer->offerDetails['account_profile_details']['account_number'] }}</p>
                        </div>
                        <div class="text-wrap">
                            <p class="text-xl font-bold">{{ Number::currency((float) $consumer->offerDetails['account_profile_details']['current_balance']) }}</p>
                        </div>
                    </div>
                @endif

                @if ($consumer->offerDetails['offer_summary'] ?? false)

                    @php
                        $consumerNegotiationType = $consumer->consumerNegotiation->negotiation_type;
                        $consumerNegotiationInstallmentType = $consumer->consumerNegotiation->installment_type;
                    @endphp
                    <div
                        x-data="datePicker"
                        class="mt-4"
                    >
                        <div class="grid grid-cols-1 items-start gap-2">
                            {{-- My Last Offer --}}
                            <div class="border rounded-lg px-4 sm:px-5 py-3">
                                <h3 class="text-lg text-black font-semibold">{{ __('My Last Offer') }}</h3>
                                <div class="grid grid-cols-2 gap-4 mt-2">
                                    <div>
                                        <p class="text-black text-xs+ sm:text-sm+ font-semibold">
                                            {{ $consumerNegotiationType === NegotiationType::INSTALLMENT
                                                ? __('Payment Plan')
                                                : __('One-Time Settlement Offer')
                                            }}
                                        </p>
                                        <p class="text-primary text-base sm:text-lg font-bold">
                                            {{ $consumerNegotiationType === NegotiationType::INSTALLMENT
                                                ? Number::currency((float) $consumer->consumerNegotiation->monthly_amount) .' / '. $consumerNegotiationInstallmentType?->value
                                                : $consumer->offerDetails['offer_summary']['my_last_offer']['one_time_settlement']
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-black text-xs+ sm:text-sm+ font-semibold">
                                            {{ $consumerNegotiationType === NegotiationType::INSTALLMENT
                                                ? __('First Payment Date')
                                                : __('Settlement Payment Date')
                                            }}
                                        </p>
                                        <p class="text-primary text-base sm:text-lg font-bold">
                                            {{ $consumer->offerDetails['offer_summary']['my_last_offer']['first_payment_date'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Creditor's Offer --}}
                            <div class="border rounded-lg px-4 sm:px-5 py-3">
                                <h3 class="text-lg text-black font-semibold">{{ __('Creditor\'s Offer') }}</h3>
                                <div class="grid grid-cols-2 gap-4 mt-2">
                                    <div>
                                        <p class="text-black text-xs+ sm:text-sm+ font-semibold">
                                            {{ $consumerNegotiationType === NegotiationType::INSTALLMENT
                                                ? __('Payment Plan')
                                                : __('One-Time Settlement Offer')
                                            }}
                                        </p>
                                        <p class="text-primary text-base sm:text-lg font-bold">
                                            {{ $consumerNegotiationType === NegotiationType::INSTALLMENT
                                                ? Number::currency((float) $consumer->consumerNegotiation->counter_monthly_amount) .' / '.$consumerNegotiationInstallmentType?->value
                                                : $consumer->offerDetails['offer_summary']['creditor_offer']['one_time_settlement']
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-black text-xs+ sm:text-sm+ font-semibold">
                                            {{ $consumerNegotiationType === NegotiationType::INSTALLMENT
                                                ? __('First Payment Date')
                                                : __('Settlement Payment Date')
                                            }}
                                        </p>
                                        <p class="text-primary text-base sm:text-lg font-bold">
                                            {{ $consumer->offerDetails['offer_summary']['creditor_offer']['first_payment_date'] }}
                                        </p>
                                    </div>
                                </div>
                                {{-- Creditor note --}}
                                <div class="flex space-x-2 items-baseline mt-2">
                                    <p class="text-black text-xs+ sm:text-sm+ font-semibold">{{ __('Note:') }}</p>
                                    <span
                                        class="text-black"
                                        x-tooltip.placement.bottom="@js($consumer->offerDetails['offer_summary']['creditor_offer']['counter_note'])"
                                    >
                                        {{ $consumer->offerDetails['offer_summary']['creditor_offer']['counter_note'] !== 'N/A' ? str()->limit($consumer->offerDetails['offer_summary']['creditor_offer']['counter_note'], 60) : '-' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Counter Offer --}}
                            <template x-if="sendCounterOffer">
                                <div class="border rounded-lg px-4 sm:px-5 py-3">
                                    <div class="flex items-center justify-between text-black">
                                        <h3 class="text-lg font-semibold">{{ __('Make a New Offer') }}</h3>
                                        @if ($consumerNegotiationType === NegotiationType::INSTALLMENT)
                                            <span class="badge whitespace-nowrap rounded-md bg-primary/10 text-primary">
                                                {{ $consumerNegotiationInstallmentType?->displayName() }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 mt-2">
                                        <div>
                                            <p class="text-black text-xs+ sm:text-sm+ font-semibold">
                                                {{ $consumerNegotiationType === NegotiationType::INSTALLMENT
                                                    ? __('Plan Payment')
                                                    : __('One-Time Settlement Offer')
                                                }}
                                            </p>
                                            @if ($consumerNegotiationType === NegotiationType::PIF)
                                                <template x-if="sendCounterOffer">
                                                    <div>
                                                        <x-form.input-group
                                                            :placeholder="__('Enter Amount')"
                                                            class="w-full"
                                                            type="text"
                                                            icon="$"
                                                            wire:model="form.monthly_amount"
                                                            name="form.monthly_amount"
                                                        />
                                                    </div>
                                                </template>
                                            @endif
                                            @if ($consumerNegotiationType === NegotiationType::INSTALLMENT)
                                                <template x-if="sendCounterOffer">
                                                    <div>
                                                        <x-form.input-group
                                                            :placeholder="__('Enter Amount')"
                                                            class="w-full"
                                                            type="text"
                                                            icon="$"
                                                            wire:model="form.monthly_amount"
                                                            name="form.monthly_amount"
                                                        />
                                                    </div>
                                                </template>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-black text-xs+ sm:text-sm+ font-semibold">
                                                {{ $consumerNegotiationType === NegotiationType::INSTALLMENT
                                                    ? __('First Pay Date')
                                                    : __('Settlement Payment Date')
                                                }}
                                            </p>
                                            @php
                                                $defaultDate = today()->lt($consumer->consumerNegotiation->first_pay_date)
                                                    ? $consumer->consumerNegotiation->first_pay_date->toDateString()
                                                    : today()->toDateString();
                                            @endphp
                                            @if ($consumerNegotiationType === NegotiationType::PIF)
                                                <div>
                                                    <div wire:ignore>
                                                        <input
                                                            type="text"
                                                            wire:model="form.counter_first_pay_date"
                                                            x-init="flatPickr(@js($defaultDate))"
                                                            class="form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary w-full"
                                                            placeholder="{{ __('mm/dd/yyyy') }}"
                                                        />
                                                    </div>
                                                    @error('form.counter_first_pay_date')
                                                        <div class="mt-2">
                                                            <span class="text-error text-sm+">
                                                                {{ $message }}
                                                            </span>
                                                        </div>
                                                    @enderror
                                                </div>
                                            @endif

                                            @if($consumerNegotiationType === NegotiationType::INSTALLMENT)
                                                <div>
                                                    <div wire:ignore>
                                                        <input
                                                            type="text"
                                                            wire:model="form.counter_first_pay_date"
                                                            x-init="flatPickr(@js($defaultDate))"
                                                            class="form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary w-full"
                                                            placeholder="{{ __('mm/dd/yyyy') }}"
                                                        />
                                                    </div>
                                                    @error('form.counter_first_pay_date')
                                                        <div class="mt-2">
                                                            <span class="text-error text-sm+">
                                                                {{ $message }}
                                                            </span>
                                                        </div>
                                                    @enderror
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <template x-if="sendCounterOffer">
                            <div class="flex justify-between items-center gap-2 rounded-bl-lg rounded-br-lg border border-slate-200 p-3 mt-3">
                                <span class="font-semibold text-black text-nowrap">
                                    {{ __('Notes from You') }}
                                </span>
                                <div>
                                    <textarea
                                        class="form-textarea w-full mt-1 resize-none rounded-lg border border-slate-300 bg-transparent p-2.5 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary"
                                        wire:model="form.counter_note"
                                        placeholder="{{ __('Enter Note For Your Creditor') }}"
                                        rows="2"
                                        cols="50"
                                    ></textarea>
                                    @error('form.counter_note')
                                        <div class="mt-2">
                                            <span class="text-error text-sm+">
                                                {{ $message }}
                                            </span>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </template>
                    </div>

                    <x-slot name="footer">
                        <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-3 pt-3">
                            <button
                                type="button"
                                x-show="! sendCounterOffer"
                                wire:click="acceptPayment"
                                wire:target="acceptPayment"
                                wire:loading.class="opacity-50"
                                wire:loading.attr="disabled"
                                class="btn border select-none border-success/30 bg-success/10 font-medium text-success hover:bg-success/20 focus:bg-success/20 active:bg-success/25 disabled:opacity-50"
                            >
                                <x-lucide-loader-2
                                    wire:target="acceptPayment"
                                    wire:loading
                                    class="animate-spin size-5 mr-2"
                                />
                                <span>{{ __('Accept') }}</span>
                            </button>

                            <button
                                type="button"
                                variant="primary"
                                x-show="! sendCounterOffer"
                                x-on:click="sendCounterOffer = true"
                                class="btn border border-primary/30 bg-primary/10 text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 disabled:opacity-50 font-medium"
                            >
                                <span> {{ __('Counter') }} </span>
                            </button>

                            <div x-show="!sendCounterOffer">
                                <livewire:consumer.my-account.report-not-paying
                                    :$consumer
                                    view="view-offer"
                                />
                            </div>

                            <div
                                x-show="sendCounterOffer"
                                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3"
                                x-on:close-dialog-of-counter-offer.window="() => {
                                    sendCounterOffer = false;
                                    dialogOpen = false;
                                    $wire.$parent.$refresh()
                                }"
                            >
                                <x-form.default-button
                                    type="button"
                                    x-on:click="sendCounterOffer = false"
                                >
                                    {{ __('Cancel') }}
                                </x-form.default-button>
                                <x-form.button
                                    type="button"
                                    variant="info"
                                    wire:click="submitCounterOffer"
                                    wire:loading.attr="disabled"
                                    wire:target="submitCounterOffer"
                                    class="disabled:opacity-50"
                                >
                                    {{ __('Send') }}
                                </x-form.button>
                            </div>
                        </div>
                    </x-slot>
                @endif
            </x-consumer.dialog.panel>
        </x-consumer.dialog>
    </div>

    @script
        <script>
            Alpine.data('datePicker', () => ({
                    flatPickrInstance: null,
                    init() {
                        this.$wire.form.counter_first_pay_date = @js($defaultDate)
                    },
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
                })
            )
        </script>
    @endscript
</div>
