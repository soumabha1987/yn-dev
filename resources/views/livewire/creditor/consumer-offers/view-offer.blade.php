@use('Illuminate\Support\Str')
@use('Illuminate\Support\Number')
@use('App\Enums\NegotiationType')

<div x-data="{ sendCounterOffer: false, isAcceptModel: false, isDeclineModel: false }">
    <x-dialog>
        <x-dialog.open>
            @if ($isMenuItem)
                <x-consumer.menu.close>
                    <x-consumer.menu.item>
                        <div @close-menu.window="menuOpen = false">
                            <span>{{ __('View Offer') }}</span>
                        </div>
                    </x-consumer.menu.item>
                </x-consumer.menu.close>
            @else
                <x-form.button
                    type="button"
                    variant="primary"
                    class="text-xs sm:text-sm+ py-1.5 px-3 whitespace-nowrap"
                >
                    <x-lucide-eye class="size-4.5 sm:size-5 mr-1"/>
                    {{ __('View Offer') }}
                </x-form.button>
            @endif
        </x-dialog.open>

        <div
            wire:model="sendCounterOffer"
            x-modelable="sendCounterOffer"
        >
            <x-dialog.panel
                @click.outside.stop="isAcceptModel || isDeclineModel ? dialogOpen = true : ''"
                :need-dialog-panel="false"
                size="5xl"
            >
                <x-slot name="heading">
                    <span x-text="sendCounterOffer ? @js(__('Counter Offer')) : @js(__('Offer Details'))" />
                </x-slot>
                <h4 class="flex items-center justify-between text-lg font-medium text-slate-800 underline underline-offset-4 decoration-2 pb-3 rounded-lg">
                    <span>{{ __('Account Profile') }}</span>
                </h4>
                <div class="mb-2">
                    <x-table>
                        <x-slot name="tableHead">
                            <x-table.tr class="text-sm w-50 border !border-slate-200">
                                <x-table.td class="w-1/4 font-extrabold text-nowrap">{{ __('Full Name :') }}</x-table.td>
                                <x-table.td>{{ Str::title($consumer->first_name . ' ' . $consumer->last_name) }}</x-table.td>
                            </x-table.tr>
                        </x-slot>
                        <x-slot name="tableBody" class="border">
                            <x-table.tr class="text-sm">
                                <x-table.td class="w-1/4 font-extrabold">{{ __('Member Account Number :') }}</x-table.td>
                                <x-table.td>{{ $consumer->member_account_number }}</x-table.td>
                                <x-table.td class="w-1/4 font-extrabold">{{ __('Reason From Consumer :') }}</x-table.td>
                                <x-table.td>{{ $consumerNegotiation->reason ?? 'N/A' }}</x-table.td>
                            </x-table.tr>
                            <x-table.tr class="text-sm">
                                <x-table.td class="w-1/4 font-extrabold">{{ __('Beginning Balance :') }}</x-table.td>
                                <x-table.td>{{ Number::currency((float) $consumer->current_balance ?? 0) }}</x-table.td>
                                <x-table.td class="w-1/4 font-extrabold">{{ __('Payment Profile Set Up :') }}</x-table.td>
                                <x-table.td>{{ $consumer->payment_setup ? __('Yes') : __('No') }}</x-table.td>
                            </x-table.tr>
                            <x-table.tr class="text-sm">
                                <x-table.td class="w-1/4 font-extrabold">{{ __('Pay Term Offer Source :') }}</x-table.td>
                                <x-table.td>{{ $payTermSource }}</x-table.td>
                                <x-table.td class="w-1/4 font-extrabold">{{ __('Negotiation Type :') }}</x-table.td>
                                <x-table.td>
                                    {{
                                        $consumerNegotiation->negotiation_type === NegotiationType::PIF
                                        ? __('Pay in Full')
                                        : __('Installment (:installmentType)', ['installmentType' => $consumerNegotiation->installment_type->displayName()])
                                     }}
                                </x-table.td>
                            </x-table.tr>
                        </x-slot>
                    </x-table>
                </div>
                <h4 class="flex items-center justify-between text-lg font-medium text-slate-800 underline underline-offset-4 decoration-2 pb-3 rounded-lg">
                    <span>{{ __('Negotiation Profile') }}</span>
                </h4>
                <div class="mb-2">
                    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                        <x-table>
                            <x-slot name="tableHead" class="border-x">
                                <x-table.tr class="text-sm">
                                    <x-table.th>{{ __('Offer Description') }}</x-table.th>
                                    <x-table.th>{{ __('Our Original Offer') }}</x-table.th>
                                    <x-table.th>{{ __('Consumer Offer') }}</x-table.th>
                                    <x-table.th>{{ __('Our Offer') }}</x-table.th>
                                </x-table.tr>
                            </x-slot>
                            <x-slot name="tableBody" class="border">
                                @if ($consumerNegotiation->negotiation_type === NegotiationType::PIF)
                                    <x-table.tr>
                                        <x-table.td>{{ __('Settlement Discount Offer') }}</x-table.td>
                                        <x-table.td>
                                            {{ __(':percentage ( :amount )',
                                                [
                                                    'percentage' => Number::percentage($calculatedData['offer']['settlement_discount_offer_percentage'] ?? 0),
                                                    'amount' => Number::currency((float) $calculatedData['offer']['settlement_discount_offer_amount'] ?? 0),
                                                ]
                                            )}}
                                        </x-table.td>
                                        <x-table.td>{{ Number::currency((float) $calculatedData['consumer_offer']['settlement_discount_offer_amount'] ?? 0) }}</x-table.td>
                                        <template x-if="! sendCounterOffer">
                                            <x-table.td>
                                                {{
                                                    $calculatedData['creditor_offer']['settlement_discount_offer_amount']
                                                        ? Number::currency((float) $calculatedData['creditor_offer']['settlement_discount_offer_amount'])
                                                        : '-'
                                                }}
                                            </x-table.td>
                                        </template>
                                        <template x-if="sendCounterOffer">
                                            <x-table.td>
                                                <x-form.input-group
                                                    :label="''"
                                                    :placeholder="__('Enter Amount')"
                                                    class="w-full"
                                                    type="text"
                                                    icon="$"
                                                    wire:model="form.settlement_discount_amount"
                                                    name="form.settlement_discount_amount"
                                                />
                                            </x-table.td>
                                        </template>
                                    </x-table.tr>
                                @else
                                    <x-table.tr>
                                        <x-table.td>{{ __('Settlement Discount Offer') }}</x-table.td>
                                        <x-table.td>
                                            {{ __(':percentage ( :amount )',
                                                [
                                                    'percentage' => Number::percentage($calculatedData['offer']['settlement_discount_offer_percentage'] ?? 0),
                                                    'amount' => Number::currency((float) $calculatedData['offer']['settlement_discount_offer_amount'] ?? 0),
                                                ]
                                            )}}
                                        </x-table.td>
                                        <x-table.td> - </x-table.td>
                                        <x-table.td> - </x-table.td>
                                    </x-table.tr>
                                @endif
                                @if ($consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT)
                                    <x-table.tr>
                                        <x-table.td>{{ __('Payment Plan Offer Payoff Bal.') }}</x-table.td>
                                        <x-table.td>
                                            {{ __(':percentage ( :amount )',
                                                [
                                                    'percentage' => Number::percentage($calculatedData['offer']['payment_plan_offer_percentage'] ?? 0),
                                                    'amount' => Number::currency((float) $calculatedData['offer']['payment_plan_offer_amount'] ?? 0),
                                                ]
                                            )}}
                                        </x-table.td>
                                        <x-table.td>{{ Number::currency((float) $calculatedData['consumer_offer']['payment_plan_offer_amount'] ?? 0) }}</x-table.td>
                                        <template x-if="! sendCounterOffer">
                                            <x-table.td>
                                                {{
                                                    $calculatedData['creditor_offer']['payment_plan_offer_amount']
                                                        ? Number::currency((float) $calculatedData['creditor_offer']['payment_plan_offer_amount'])
                                                        : '-'
                                                }}
                                            </x-table.td>
                                        </template>
                                        <template x-if="sendCounterOffer">
                                            <x-table.td>
                                                <x-form.input-group
                                                    :label="''"
                                                    :placeholder="__('Enter Amount')"
                                                    class="w-full"
                                                    type="text"
                                                    icon="$"
                                                    wire:model="form.payment_plan_discount_amount"
                                                    name="form.payment_plan_discount_amount"
                                                />
                                            </x-table.td>
                                        </template>
                                    </x-table.tr>
                                    <x-table.tr>
                                        <x-table.td>{{ __('Installment Amount') }}</x-table.td>
                                        <x-table.td>
                                            {{ __(':percentage ( :amount )',
                                                [
                                                    'percentage' => Number::percentage($calculatedData['offer']['minimum_monthly_payment_percentage'] ?? 0),
                                                    'amount' => Number::currency((float) $calculatedData['offer']['minimum_monthly_payment'] ?? 0). ' / monthly',
                                                ]
                                            )}}
                                        </x-table.td>
                                        <x-table.td>{{ $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT ? Number::currency($calculatedData['consumer_offer']['minimum_monthly_payment'] ?? 0).' / '.$consumerNegotiation->installment_type->value : '-'  }}</x-table.td>
                                        <template x-if="! sendCounterOffer">
                                            <x-table.td>
                                                {{
                                                    $calculatedData['creditor_offer']['minimum_monthly_payment']
                                                        ? Number::currency((float) $calculatedData['creditor_offer']['minimum_monthly_payment']). ' / '.$consumerNegotiation->installment_type->value
                                                        : '-'
                                                }}
                                            </x-table.td>
                                        </template>
                                        <template x-if="sendCounterOffer">
                                            <x-table.td>
                                                <x-form.input-group
                                                    :label="''"
                                                    :placeholder="__('Enter Amount')"
                                                    class="w-full"
                                                    type="text"
                                                    icon="$"
                                                    iconRight="{{ $consumerNegotiation->installment_type?->value }}"
                                                    wire:model="form.monthly_amount"
                                                    name="form.monthly_amount"
                                                />
                                            </x-table.td>
                                        </template>
                                    </x-table.tr>
                                @else
                                    <x-table.tr>
                                        <x-table.td>{{ __('Payment Plan Offer Payoff Bal.') }}</x-table.td>
                                        <x-table.td>
                                            {{ __(':percentage ( :amount )',
                                                [
                                                    'percentage' => Number::percentage($calculatedData['offer']['payment_plan_offer_percentage'] ?? 0),
                                                    'amount' => Number::currency((float) $calculatedData['offer']['payment_plan_offer_amount'] ?? 0),
                                                ]
                                            )}}
                                        </x-table.td>
                                        <x-table.td> - </x-table.td>
                                        <x-table.td> - </x-table.td>
                                    </x-table.tr>
                                    <x-table.tr>
                                        <x-table.td>{{ __('Installment Amount') }}</x-table.td>
                                        <x-table.td>
                                            {{ __(':percentage ( :amount )',
                                                [
                                                    'percentage' => Number::percentage($calculatedData['offer']['minimum_monthly_payment_percentage'] ?? 0),
                                                    'amount' => Number::currency((float) $calculatedData['offer']['minimum_monthly_payment'] ?? 0). ' / monthly',
                                                ]
                                            )}}
                                        </x-table.td>
                                        <x-table.td> - </x-table.td>
                                        <x-table.td> - </x-table.td>
                                    </x-table.tr>
                                @endif
                                <x-table.tr>
                                    {{-- TODO: Improve naming for the first payment date when negotiation type is `PIF` --}}
                                    <x-table.td>{{ __('1st Payment Due Date') }}</x-table.td>
                                    <x-table.td>
                                        {{ __(':day ( :date )',
                                            [
                                                'day' => $calculatedData['offer']['first_payment_day'],
                                                'date' => $calculatedData['offer']['first_payment_date']->format('M d, Y'),
                                            ]
                                        )}}
                                    </x-table.td>
                                    <x-table.td>{{ $calculatedData['consumer_offer']['first_payment_date']->format('M d, Y') ?? '-' }}</x-table.td>
                                    <template x-if="! sendCounterOffer">
                                        <x-table.td>{{ $calculatedData['creditor_offer']['first_payment_date']?->format('M d, Y') ?? '-' }}</x-table.td>
                                    </template>
                                    <template x-if="sendCounterOffer">
                                        @php
                                            $defaultDate = $consumerNegotiation->counter_first_pay_date && today()->lt($consumerNegotiation->counter_first_pay_date)
                                                ? $consumerNegotiation->counter_first_pay_date->toDateString()
                                                : today()->toDateString();
                                        @endphp
                                        <x-table.td x-data="datePicker">
                                            <div wire:ignore>
                                                <input
                                                    type="text"
                                                    wire:model="form.counter_first_pay_date"
                                                    x-init="flatPickr(@js($defaultDate))"
                                                    class="form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary w-full"
                                                    placeholder="{{ __('mm/dd/yyyy') }}"
                                                    required
                                                    autocomplete="off"
                                                />
                                            </div>
                                            @error('form.counter_first_pay_date')
                                                <div class="mt-2">
                                                    <span class="text-error text-sm+">
                                                        {{ $message }}
                                                    </span>
                                                </div>
                                            @enderror
                                        </x-table.td>
                                    </template>
                                </x-table.tr>
                            </x-slot>
                        </x-table>
                    </div>
                    <div class="flex flex-col border border-slate-200 px-4 mt-3">
                        <div class="flex items-center gap-2 my-2">
                            <span class="font-semibold text-black">
                                {{ __('Notes from Consumer :') }}
                            </span>
                            <span>{{ $this->consumerNegotiation->note }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 rounded-bl-lg rounded-br-lg border border-slate-200 p-3 mt-3">
                        <span class="font-semibold text-black text-nowrap">
                            {{ __('Notes from You :') }}
                        </span>
                        <span x-show="! sendCounterOffer">{{ $calculatedData['creditor_offer']['counter_note'] }}</span>
                        <div
                            x-show="sendCounterOffer"
                            class="w-full"
                        >
                            <textarea
                                class="form-textarea w-full mt-2 resize-none rounded-lg border border-slate-300 bg-transparent p-2.5 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary"
                                wire:model="form.counter_note"
                                placeholder="{{ __('Enter Note For Your Consumer') }}"
                                rows="2"
                                cols="50"
                            ></textarea>
                            <div
                                x-on:scroll-into-counter-note.window="$el.scrollIntoView({ behavior: 'smooth' })"
                                class="mt-2"
                            >
                                <span class="text-error text-sm+">
                                    {{ $errors->first('form.counter_note') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                @if (! $isNotEditable)
                    <x-slot name="footer">
                        <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-3 pt-3 sm:pt-6">
                            <div
                                x-show="! sendCounterOffer"
                                @close-dialog.window="() => {
                                    isDeclineModel = false
                                    isAcceptModel = false
                                    $dispatch('refresh-parent')
                                }"
                            >
                                <x-form.button
                                    type="button"
                                    variant="success"
                                    class="w-full"
                                    @click="isAcceptModel = true"
                                >
                                    <span>{{ __('Accept') }}</span>
                                </x-form.button>
                            </div>
                            <div x-show="! sendCounterOffer">
                                <x-form.button
                                    type="button"
                                    variant="error"
                                    class="w-full"
                                    @click="isDeclineModel = true"
                                >
                                    <span>{{ __('Decline') }}</span>
                                </x-form.button>
                            </div>
                            <div x-show="! sendCounterOffer">
                                <x-form.button
                                    type="button"
                                    variant="primary"
                                    class="disabled:opacity-50 w-full"
                                    @click="sendCounterOffer = true"
                                >
                                    {{ __('Counter') }}
                                </x-form.button>
                            </div>
                            <div
                                x-show="sendCounterOffer"
                                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3"
                                @close-dialog-of-counter-offer.window="() => {
                                    dialogOpen = false
                                    $dispatch('refresh-parent')
                                }"
                            >
                                <x-form.default-button
                                    type="button"
                                    @click="sendCounterOffer = false"
                                >
                                    {{ __('Cancel') }}
                                </x-form.default-button>

                                <x-form.button
                                    type="button"
                                    variant="info"
                                    class="disabled:opacity-50"
                                    wire:click="submitCounterOffer"
                                >
                                    {{ __('Send') }}
                                </x-form.button>
                            </div>
                        </div>
                    </x-slot>
                @endif
            </x-dialog.panel>
        </div>
    </x-dialog>

    <x-dialog x-model="isAcceptModel">
        <x-dialog.panel
            size="xl"
            :need-dialog-panel="false"
            confirm-box
        >
            <x-slot name="svg">
                <x-emoji-hand-shake class="inline size-20" />
            </x-slot>
            <x-slot name="message">
                <div class="text-lg text-black font-medium">
                    @if ($consumerNegotiation->negotiation_type === NegotiationType::PIF)
                        <p>
                            {{
                                __("I accept :consumerFirstName  Settlement Offer!", ['consumerFirstName' => $consumer->first_name])
                            }}
                        </p>
                        <p>
                            {{
                                __("Settlement amount of :amount due on or before :date",
                                [
                                    'amount' => Number::currency((float) $calculatedData['consumer_offer']['settlement_discount_offer_amount'] ?? 0),
                                    'date' => $calculatedData['consumer_offer']['first_payment_date']->format('M d, y')
                                ])
                            }}
                        </p>
                    @else
                        <p>
                            {{
                                __("I accept :consumerFirstName  Payment Plan Offer!", ['consumerFirstName' => $consumer->first_name])
                            }}
                        </p>
                        <p>
                            {{
                                __(":frequency payment of :amount starting on :date",
                                [
                                    'frequency' => Str::ucfirst($consumerNegotiation->installment_type?->value),
                                    'amount' => Number::currency((float) $calculatedData['consumer_offer']['minimum_monthly_payment'] ?? 0),
                                    'date' => $calculatedData['consumer_offer']['first_payment_date']->format('M d, Y')
                                ])
                            }}
                        </p>
                    @endif
                </div>
            </x-slot>
            <x-slot name="buttons">
                <div class="flex flex-col sm:flex-row justify-center items-stretch sm:items-center gap-2">
                    <x-form.button
                        type="button"
                        variant="primary"
                        class="disabled:opacity-50 w-full sm:w-auto font-medium"
                        wire:click="acceptOffer"
                        wire:loading.attr="disabled"
                    >
                        <div
                            wire:loading.flex
                            wire:target="acceptOffer"
                            class="flex items-center gap-x-2"
                        >
                            <x-lucide-loader-2 class="size-5 animate-spin" />
                            <span class="whitespace-nowrap">{{ __('Accepting') }}</span>
                        </div>
                        <span
                            wire:target="acceptOffer"
                            wire:loading.remove
                            class="whitespace-nowrap"
                        >
                            {{ __('Yes, I accept the offer!') }}
                        </span>
                    </x-form.button>
                    <x-form.default-button
                        type="button"
                        class="whitespace-nowrap"
                        @click.stop="isAcceptModel = false"
                    >
                        {{ __('Close') }}
                    </x-form.default-button>
                </div>
            </x-slot>
        </x-dialog.panel>
    </x-dialog>

    <x-dialog x-model="isDeclineModel">
        <x-dialog.panel size="3xl" confirm-box>
            <x-slot name="message">
                <div class="text-left">
                    <p class="font-semibold text-lg mb-4">
                        {{ __('Please confirm you are declining this offer and closing this negotiation.') }}
                    </p>
                    <label class="inline-flex items-start mb-4">
                        <x-form.input-checkbox
                            label=""
                            name="test"
                            wire:click="declineOffer({{ $consumer->id }})"
                            class="my-1"
                        />
                        <span>
                            {{ __('Yes. Decline/close this negotiation (please note if the account is active, the consumer continues to have access to accept your offers, create a new offer they can afford, dispute, or report they\'re not paying.)') }}
                        </span>
                    </label>
                    <div class="text-sm">
                        <span class="font-extrabold text-lg">{{ __('Note:') }}</span>
                        <span>
                            {{ __('If you would like to delete this account from YouNegotiate: go to') }}
                            <a
                                href="{{ route('creditor.import-consumers.index') }}"
                                class="text-blue-600 underline"
                            >
                                {{ __('Upload a File') }}
                            </a>,
                            {{ __('Choose your Header Profile; and Upload a CSV DELETE file with the Original account number. The account will be closed in both your and the consumer\'s portal account.') }}
                        </span>
                    </div>
                </div>
            </x-slot>
        </x-dialog.panel>
    </x-dialog>

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
            }))
        </script>
    @endscript
</div>
