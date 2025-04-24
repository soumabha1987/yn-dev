@use('App\Enums\BankAccountType')
@use('App\Enums\MerchantType')
@props(['merchants', 'termsAndCondition' => null, 'consumer', 'isDisplayName' => false,  'savedCards' => null])

<div
    x-data="otherMerchants"
    class="col-span-2 mt-20 w-full p-4 sm:p-5"
    :class="{
        '!mt-4' : method === 'helping-hand-link',
        'card' : method !== 'helping-hand-link'
    }"
    x-on:update-card-number.window="card_number = ''"
>
    <x-loader
        wire:loading
        wire:target="makePayment"
    />

    @if ($merchants->contains('merchant_type', MerchantType::CC))
        <div x-show="method === @js(MerchantType::CC->value)">
            <div class="relative mx-auto -mt-20 h-40 w-72 rounded-lg text-white shadow-xl transition-transform hover:scale-110 lg:h-48 lg:w-80">
                <div class="size-full rounded-lg bg-[conic-gradient(at_bottom_right,_var(--tw-gradient-stops))] from-teal-600 via-blue-200 to-neutral-100"></div>
                <div class="absolute top-0 flex size-full flex-col justify-between p-4 sm:p-5">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-base font-semibold text-primary">
                                {{ __('Name') }}
                            </p>
                            <span
                                x-text="$wire.form.card_holder_name"
                                class="font-bold capitalize tracking-wide text-xl text-primary"
                            ></span>
                        </div>
                        <x-lucide-credit-card class="size-6 text-primary" />
                    </div>
                    <div class="flex justify-between">
                        <div>
                            <p class="text-base font-semibold text-primary">
                                {{ __('Card Number') }}
                            </p>
                            <span
                                x-text="card_number"
                                class="font-bold capitalize tracking-wide  text-xl text-primary"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            x-show="method === @js(MerchantType::CC->value)"
            class="flex items-center justify-between py-4"
        >
            <p class="text-lg sm:text-xl font-semibold text-primary">
                {{ __('Credit Card') }}
            </p>
        </div>

        <template x-if="method === @js(MerchantType::CC->value)">
            <form
                id="make-payment-form"
                wire:submit="makePayment"
                autocomplete="off"
            >
                <div class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="block">
                                <span class="font-semibold tracking-wide text-black text-sm lg:text-base">
                                    {{ __('Card Number') }}<span class="text-error">*</span>
                                </span>
                                <span class="relative mt-1.5 flex">
                                    <input
                                        type="tel"
                                        x-model="card_number"
                                        x-mask:dynamic="$input.startsWith('34') || $input.startsWith('37') ? '9999 999999 99999' : '9999 9999 9999 9999'"
                                        class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 hover:border-slate-400 focus:border-accent"
                                        placeholder="{{ __('Card Number') }}"
                                        autocomplete="off"
                                        required
                                    >
                                </span>
                                <div x-data="{
                                    savedCardsDialogOpen: false,
                                    card_holder_name: '', 
                                    useCard(card) {
                                        this.card_holder_name = card.card_holder_name;
                                        $wire.set('form.card_holder_name', card.card_holder_name); 

                                        this.expiry = card.expiry; 
                                        $wire.set('form.expiry', card.expiry); 

                                        @this.call('getDecryptedCardNumber', card.encrypted_card_data).then((decryptedCardNumber) => {
                                            this.card_number = decryptedCardNumber;
                                            $wire.set('form.card_number', decryptedCardNumber);
                                        });
                                    }
                                }">

                                    <x-consumer.dialog>
                                        <x-consumer.dialog.open>
                                            <span class="text-blue-600 text-lg font-semibold hover:underline cursor-pointer">
                                                {{ __('View Saved Cards') }}
                                            </span>
                                        </x-consumer.dialog.open>

                                        <x-consumer.dialog.panel :heading="__('Saved Cards')" class="h-[400px]">
                                            <div class="space-y-4 px-4 py-2 overflow-y-auto">
                                                @forelse ($savedCards as $card)
                                                    <div class="p-4 border rounded-lg shadow-sm flex justify-between items-center bg-white hover:bg-gray-50 transition-all">
                                                        <div class="flex-1">
                                                            <p class="font-semibold text-lg">{{ $card->card_holder_name }}</p>
                                                            <p class="text-sm text-gray-500">**** **** **** {{ $card->last4digit }}</p>
                                                            <p class="text-sm text-gray-400">Exp: {{ $card->expiry }}</p>
                                                        </div>
                                                        <div class="flex space-x-2">
                                                            <button
                                                                type="button"
                                                                class="text-primary text-sm"
                                                                x-on:click="$dialog.close(); $dispatch('dialog:close'); useCard({{ json_encode($card) }})"
                                                            >
                                                                {{ __('Use') }}
                                                            </button>
                                                            <x-confirm-box
                                                                :message="__('Are you sure you want to delete this card?')"
                                                                :ok-button-label="__('Delete')"
                                                                action="deleteCard({{ $card->id }})"
                                                            >
                                                                <x-menu.item>
                                                                    <span class="text-red-600">{{ __('Delete') }}</span>
                                                                </x-menu.item>
                                                            </x-confirm-box>
                                                            
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-slate-500 text-sm">{{ __('No saved cards found.') }}</p>
                                                @endforelse
                                            </div>
                                        </x-consumer.dialog.panel>
                                    </x-consumer.dialog>
                                </div>

                                @error('form.card_number')
                                    <div class="mt-1">
                                        <span class="text-error text-sm+">{{ $message }}</span>
                                    </div>
                                @enderror
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <label class="block">
                                <span class="font-semibold tracking-wide text-black text-sm lg:text-base">
                                    {{ __('Exp.') }}<span class="text-error">*</span>
                                </span>
                                <span class="relative mt-1.5 flex">
                                    <input
                                        type="text"
                                        x-on:input="expiry"
                                        x-on:blur="generateExpiry"
                                        x-mask="99/9999"
                                        maxlength="7"
                                        pattern="(0[1-9]|1[0-2])\/\d{4}"
                                        class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 hover:border-slate-400 focus:border-accent"
                                        placeholder="MM/YY"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        required
                                        wire:model="form.expiry"
                                    >
                                </span>
                                <template x-if="expiryError !== ''">
                                    <div class="mt-1">
                                        <span class="text-error text-sm+" x-text="expiryError"></span>
                                    </div>
                                </template>
                                @error('form.expiry')
                                    <div class="mt-1">
                                        <span class="text-error text-sm+">{{ $message }}</span>
                                    </div>
                                @enderror
                            </label>
                            <div x-data="{ showCvv: false }">
                                <label class="block">
                                    <span class="font-semibold uppercase tracking-wide text-black lg:text-base">
                                        {{ __('CVV') }}<span class="text-error">*</span>
                                    </span>
                                    <div class="relative flex mt-1.5">
                                        <input
                                            x-bind:type="showCvv ? 'text' : 'password'"
                                            wire:model="form.cvv"
                                            class="form-input w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 hover:border-slate-400 focus:border-accent"
                                            placeholder="232"
                                            maxlength="4"
                                            pattern="\d*"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            x-on:input="$el.value = $el.value.replace(/\D/g, '')"
                                            required
                                        />
                                        <div class="absolute right-0 flex h-full w-10 items-center justify-center text-slate-400">
                                            <x-heroicon-o-eye-slash
                                                x-on:click="showCvv = !showCvv"
                                                x-show="!showCvv"
                                                class="w-5.5"
                                            />
                                            <x-heroicon-o-eye
                                                x-on:click="showCvv = !showCvv"
                                                x-show="showCvv"
                                                class="w-5.5"
                                            />
                                        </div>
                                    </div>
                                    @error('form.cvv')
                                        <div class="mt-1">
                                            <span class="text-error text-sm+">{{ $message }}</span>
                                        </div>
                                    @enderror
                                </label>
                            </div>
                        </div>
                    </div>

                    <label class="block">
                        <span class="font-semibold tracking-wide text-black text-sm lg:text-base">
                            {{ __('Name on Card') }}<span class="text-error">*</span>
                        </span>
                        <span class="relative mt-1.5 flex">
                            <input
                                type="text"
                                wire:model="form.card_holder_name"
                                class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 hover:border-slate-400 focus:border-accent"
                                placeholder="{{ __('Name on Card') }}"
                                autocomplete="off"
                                required
                            >
                        </span>
                        @error('form.card_holder_name')
                            <div class="mt-1">
                                <span class="text-error text-sm+">{{ $message }}</span>
                            </div>
                        @enderror
                    </label>

                    <x-consumer.payment.account-details :$isDisplayName />

                    <label class="block">
                        <span class="font-semibold uppercase tracking-wide text-black lg:text-base">
                            {{ __('ESign') }}<span class="text-error">*</span>
                        </span>
                        <div>
                            <label class="flex flex-col md:flex-row mt-3">
                                <div class="inline-flex items-start gap-2">
                                    <div class="shrink-0">
                                        <input
                                            wire:model="form.is_terms_accepted"
                                            type="checkbox"
                                            class="form-checkbox is-basic size-4 sm:size-4.5 my-1 rounded border-slate-400/70 bg-slate-100 checked:border-primary checked:bg-primary hover:border-primary focus:border-primary"
                                        >
                                    </div>
                                    <div class="xl:mt-0.5">
                                        @if ($termsAndCondition)
                                            {{ __('I agree to pay the scheduled payment plan totaling according to the') }}
                                        @else
                                            <span>{{ __('I agree to Debt Free Americans donation') }}</span>
                                        @endif
                                        <x-consumer.dialog class="inline-block">
                                            <span
                                                x-on:click="$event.preventDefault(); dialogOpen = true"
                                                class="underline underline-offset-2 cursor-pointer text-nowrap"
                                            >
                                                {{ __('Terms & Conditions') }}
                                            </span>

                                            <x-consumer.dialog.panel
                                                :heading="__('Terms and Condition')"
                                                size="2xl"
                                                class="h-96"
                                            >
                                                @if ($termsAndCondition)
                                                    <div class="ql-editor">
                                                        {!! $termsAndCondition !!}
                                                    </div>
                                                @else
                                                    <x-consumer.external-payment-terms-and-conditions />
                                                @endif

                                            </x-consumer.dialog.panel>
                                        </x-consumer.dialog>
                                    </div>
                                </div>
                            </label>
                            @error('form.is_terms_accepted')
                                <div class="mt-1">
                                    <span class="text-error text-sm+">{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                        @if ($termsAndCondition === null)
                            <div class="flex items-center justify-center sm:justify-normal mt-1">
                                <img src="{{ asset('images/dfa.png') }}" class="w-36">
                            </div>
                        @endif
                    </label>

                    <div>
                        <label class="inline-flex items-start gap-2 mt-3">
                            <div class="shrink-0">
                                <input
                                    wire:model="form.save_card"
                                    type="checkbox"
                                    class="form-checkbox is-basic size-4 sm:size-4.5 my-1 rounded border-slate-400/70 bg-slate-100 checked:border-primary checked:bg-primary hover:border-primary focus:border-primary"
                                >
                            </div>
                            <span class="text-black text-sm lg:text-base">
                                {{ __('Save this card for future use') }}
                            </span>
                        </label>
                    </div>


                    <div class="flex justify-center space-x-2">
                        <button
                            type="submit"
                            class="btn disabled:opacity-50 space-x-2 flex items-center min-w-[7rem] bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                            wire:loading.class="opacity-50"
                            wire:loading.attr="disabled"
                            wire:target="makePayment"
                            x-bind:disabled="expiryError !== ''"
                        >
                            <x-lucide-lock class="size-5" />
                            <span class="font-semibold text-sm sm:text-base">{{ __('Secure Pay') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </template>
    @endif

    @if ($merchants->contains('merchant_type', MerchantType::ACH))
        <div x-show="method === @js(MerchantType::ACH->value)">
            <div class="relative mx-auto -mt-20 h-40 w-72 rounded-lg text-white shadow-xl transition-transform hover:scale-110 lg:h-48 lg:w-80">
                <div class="size-full rounded-lg bg-[conic-gradient(at_bottom_right,_var(--tw-gradient-stops))] from-teal-600 via-blue-200 to-neutral-100"></div>
                <div class="absolute top-0 flex size-full flex-col justify-between p-4 sm:p-5">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-base font-semibold text-primary">{{ __('Account Number') }}</p>
                            <span
                                x-text="$wire.form.account_number"
                                class="font-bold tracking-wide text-xl text-primary"
                            ></span>
                        </div>
                        <x-lucide-landmark class="size-6 text-primary" />
                    </div>
                    <div class="flex justify-between">
                        <div>
                            <p class="text-base font-semibold text-primary">{{ __('Routing Number') }}</p>
                            <span
                                x-text="$wire.form.routing_number"
                                class="font-bold tracking-wide text-xl text-primary"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            x-show="method === @js(MerchantType::ACH->value)"
            class="flex items-center justify-between py-4"
        >
            <p class="text-lg sm:text-xl font-semibold text-primary">
                {{ __('Account Details') }}
            </p>
        </div>

        <template x-if="method === @js(MerchantType::ACH->value)">
            <form wire:submit="makePayment" autocomplete="off">
                <div class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                       <div class="lg:col-span-5">
                           <label class="block">
                               <span class="font-semibold capitalize tracking-wide text-black lg:text-base">
                                   {{ __('Account Number') }}<span class="text-error">*</span>
                               </span>
                                <span class="relative mt-1.5 flex">
                                    <input
                                        type="text"
                                        pattern="[0-9.]+"
                                        wire:model="form.account_number"
                                        class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 hover:border-slate-400 focus:border-accent"
                                        placeholder="34122345982"
                                        minlength="4"
                                        maxlength="17"
                                        autocomplete="off"
                                        required
                                    >
                                </span>
                               @error('form.account_number')
                                   <div class="mt-1">
                                       <span class="text-error text-sm+">{{ $message }}</span>
                                   </div>
                               @enderror
                           </label>
                       </div>
                       <div class="lg:col-span-5">
                           <label class="block">
                               <span class="font-semibold capitalize tracking-wide text-black lg:text-base">
                                   {{ __('Routing Number') }}<span class="text-error">*</span>
                               </span>
                               <span class="relative mt-1.5 flex">
                                    <input
                                        type="text"
                                        pattern="[0-9.]+"
                                        wire:model="form.routing_number"
                                        class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 hover:border-slate-400 focus:border-accent"
                                        placeholder="022345982"
                                        minlength="9"
                                        maxlength="9"
                                        autocomplete="off"
                                        required
                                    >
                                </span>
                               @error('form.routing_number')
                                   <div class="mt-1">
                                       <span class="text-error text-sm+">{{ $message }}</span>
                                   </div>
                               @enderror
                           </label>
                       </div>
                       <div class="lg:col-span-2">
                           <label class="block">
                               <span class="font-semibold capitalize tracking-wide text-black lg:text-base">
                                   {{ __('Type') }}<span class="text-error">*</span>
                               </span>
                               <div class="relative mt-1.5 flex">
                                    <select
                                        wire:model="form.account_type"
                                        class="form-select peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 hover:border-slate-400 focus:border-accent"
                                    >
                                        <option value="">{{ __('Select type') }}</option>
                                        @foreach (BankAccountType::displaySelectionBox() as $value => $name)
                                            <option value="{{ $value }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                               </div>
                               @error('form.account_type')
                                   <div class="mt-1">
                                       <span class="text-error text-sm+">{{ $message }}</span>
                                   </div>
                               @enderror
                           </label>
                       </div>
                    </div>


                    <x-consumer.payment.account-details :$isDisplayName />

                    <label class="block">
                        <span class="font-semibold uppercase tracking-wide text-black lg:text-base">
                            {{ __('ESign') }}<span class="text-error">*</span>
                        </span>
                        <div>
                            <label class="flex flex-col md:flex-row mt-3">
                                <div class="inline-flex items-start gap-2">
                                    <div class="shrink-0">
                                        <input
                                            wire:model="form.is_terms_accepted"
                                            type="checkbox"
                                            class="form-checkbox is-basic size-4 sm:size-4.5 my-1 rounded border-slate-400/70 bg-slate-100 checked:border-primary checked:bg-primary hover:border-primary focus:border-primary"
                                        >
                                    </div>
                                    <div class="xl:mt-0.5">
                                        @if ($termsAndCondition)
                                            {{ __('I agree to pay the scheduled payment plan totaling according to the') }}
                                        @else
                                            <span>{{ __('I agree to Debt Free Americans donation') }}</span>
                                        @endif
                                        <x-consumer.dialog class="inline-block">
                                            <span
                                                x-on:click="$event.preventDefault(); dialogOpen = true"
                                                class="underline underline-offset-2 cursor-pointer text-nowrap"
                                            >
                                                {{ __('Terms & Conditions') }}
                                            </span>

                                            <x-consumer.dialog.panel
                                                :heading="__('Terms and Condition')"
                                                size="2xl"
                                                class="h-96"
                                            >
                                                @if ($termsAndCondition)
                                                    <div class="ql-editor">
                                                        {!! $termsAndCondition !!}
                                                    </div>
                                                @else
                                                    <x-consumer.external-payment-terms-and-conditions />
                                                @endif
                                            </x-consumer.dialog.panel>
                                        </x-consumer.dialog>
                                    </div>
                                </div>
                            </label>
                            @error('form.is_terms_accepted')
                                <div class="mt-1">
                                    <span class="text-error text-sm+">{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                        @if ($termsAndCondition === null)
                            <div class="flex items-center justify-center sm:justify-normal mt-1">
                                <img src="{{ asset('images/dfa.png') }}" class="w-36">
                            </div>
                        @endif
                    </label>

                    <div class="flex justify-center space-x-2">
                        <button
                            type="submit"
                            class="btn disabled:opacity-50 space-x-2 flex items-center min-w-[7rem] bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                            wire:loading.class="opacity-50"
                            wire:loading.attr="disabled"
                            wire:target="makePayment"
                        >
                            <x-lucide-lock class="size-5" />
                            <span class="font-semibold text-sm sm:text-base">{{ __('Secure Pay') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </template>
    @endif
</div>

@script
    <script>
        Alpine.data('otherMerchants', () => ({
            card_number: '',
            expiryError: '',
            init() {
                this.$watch('card_number', value => {
                    this.$wire.form.card_number = value.replace(/[^0-9]/g, '')
                })
            },
            expiry() {
                this.expiryError = ''
                if (this.$el.value.length < 5) {
                    this.expiryError = '{{ __('The expiry field must match the format m/Y.') }}'
                    return
                }

                const enteredMonth = parseInt(this.$el.value.split('/')[0])
                if (enteredMonth < 1 || enteredMonth > 12) {
                    this.expiryError = '{{ __('The expiry month must be between 01 and 12.') }}'
                    return
                }

                const enteredYear = parseInt(this.$el.value.split('/')[1].match(/\d{2}/)[0])
                const currentYear = new Date().getFullYear().toString().substring(2, 4)
                if (enteredYear < currentYear) {
                    this.expiryError = '{{ __('Your card is expired.') }}'
                }
            },
            generateExpiry() {
                if (this.$el.value === '') return
                const year = new Date().getFullYear().toString().substring(0, 2)
                const currentDate = this.$el.value.replace(/[^0-9/]/gi, '').toString()
                const parts = currentDate.split('/')
                if (parts[1].length === 2) {
                    this.$wire.form.expiry = parts[0] + '/' + year + parts[1]
                } else {
                    this.$wire.form.expiry = currentDate
                }
            },
        }))
    </script>
@endscript
