@use('App\Enums\BankAccountType')
@use('App\Enums\MerchantType')
@props([
    'merchants',
    'termsAndCondition' => null,
    'consumer',
    'isDisplayName' => false,
])

@assets
    <script src="https://js.tilled.com/v2"></script>
@endassets

<div
    x-data="youNegotiate"
    class="col-span-2 mt-20 w-full p-4 sm:p-5"
    :class="{
        '!mt-4' : method === 'helping-hand-link',
        'card' : method !== 'helping-hand-link'
    }"
>
    <x-loader x-show="showLoader" />

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
                    <template x-if="creditCardIcon === ''">
                        <x-lucide-credit-card class="w-12 text-primary" />
                    </template>
                    <template x-if="creditCardIcon !== ''">
                        <img
                            x-bind:src="creditCardIcon"
                            class="w-12 rounded-lg"
                            alt="credit-card-icon"
                        >
                    </template>
                </div>
                <div class="flex justify-between">
                    <div>
                        <p class="text-base font-semibold text-primary">
                            {{ __('Card Number') }}
                        </p>
                        <span class="font-bold capitalize tracking-wide text-xl text-primary">
                            XXXX XXXX XXXX XXXX
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        x-show="method === @js(MerchantType::CC->value)"
        class="flex items-center justify-between py-4"
    >
        <p class="text-xl font-semibold text-primary">
            {{ __('Credit Card') }}
        </p>
    </div>

    <div x-show="method === @js(MerchantType::CC->value)">
        <form
            id="make-payment-form"
            method="POST"
            x-on:submit="makePayment"
            autocomplete="off"
        >
            <div class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label class="block">
                            <span class="font-semibold tracking-wide text-black lg:text-base">
                                {{ __('Card Number') }}<span class="text-error">*</span>
                            </span>
                            <div
                                wire:ignore
                                class="relative mt-1.5 flex"
                            >
                                <div
                                    id="tilled-js-credit-card-number"
                                    class="form-input peer h-10 w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                                >
                                    {{-- Tilled js inject the card number input via iframe --}}
                                </div>
                            </div>
                            @error('form.card_number')
                                <div class="mt-1">
                                    <span class="text-error text-sm+">{{ $message }}</span>
                                </div>
                            @enderror
                            <template x-if="errorMessages['card_number']">
                                <div class="mt-0.5">
                                    <span class="text-error text-sm+" x-text="errorMessages['card_number']"></span>
                                </div>
                            </template>
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="block">
                            <span class="font-semibold tracking-wide text-black lg:text-base">
                                {{ __('Exp.') }}<span class="text-error">*</span>
                            </span>
                            <span
                                wire:ignore
                                class="relative mt-1.5 flex"
                            >
                                <div
                                    id="tilled-js-credit-card-expiry"
                                    class="form-input peer h-10 rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                                >
                                    {{-- Tilled js inject the card expiry input via iframe --}}
                                </div>
                            </span>
                            <template x-if="errorMessages['expiry']">
                                <div class="mt-0.5">
                                    <span class="text-error text-sm+" x-text="errorMessages['expiry']"></span>
                                </div>
                            </template>
                        </label>
                        <label class="block">
                            <span class="font-semibold uppercase tracking-wide text-black lg:text-base">
                                {{ __('CVV') }}<span class="text-error">*</span>
                            </span>
                            <span
                                wire:ignore
                                class="relative mt-1.5 flex"
                            >
                                <div
                                    id="tilled-js-credit-card-cvv"
                                    class="form-input peer h-10 rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                                >
                                    {{-- Tilled js inject the card cvv input via iframe --}}
                                </div>
                            </span>
                            <template x-if="errorMessages['cvv']">
                                <div class="mt-0.5">
                                    <span class="text-error text-sm+" x-text="errorMessages['cvv']"></span>
                                </div>
                            </template>
                        </label>
                    </div>
                </div>

                <label class="block">
                    <span class="font-semibold tracking-wide text-black lg:text-base">
                        {{ __('Name on Card') }}<span class="text-error">*</span>
                    </span>
                    <span class="relative mt-1.5 flex">
                        <input
                            type="text"
                            wire:model="form.card_holder_name"
                            class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                            placeholder="{{ __('Card Holder Number') }}"
                            required
                        >
                    </span>
                    <template x-if="errorMessages['card_holder_name']">
                        <div class="mt-0.5">
                            <span class="text-error text-sm+" x-text="errorMessages['card_holder_name']"></span>
                        </div>
                    </template>
                </label>

                <x-consumer.payment.account-details :$isDisplayName />

                <label class="block">
                    <span class="font-semibold uppercase tracking-wide text-black lg:text-base">
                        {{ __('ESign') }}<span class="text-error">*</span>
                    </span>
                    <div>
                        <label class="inline-flex mt-3">
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
                                            class="underline underline-offset-2 cursor-pointer"
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
                        <template x-if="errorMessages['terms_accepted']">
                            <div class="mt-0.5">
                                <span class="text-error text-sm+" x-text="errorMessages['terms_accepted']"></span>
                            </div>
                        </template>
                    </div>
                </label>
                @if ($termsAndCondition === null)
                    <div class="flex items-center justify-center sm:justify-normal">
                        <img src="{{ asset('images/dfa.png') }}" class="w-36">
                    </div>
                @endif

                <div class="flex justify-center space-x-2">
                    <button
                        type="submit"
                        class="btn disabled:opacity-50 space-x-2 flex items-center min-w-[7rem] bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                        x-bind:disabled="submitButtonIsDisabled"
                    >
                        <x-lucide-lock class="size-5" />
                        <span class="font-semibold text-lg">{{ __('Secure Pay') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div x-show="method === @js(MerchantType::ACH->value)">
        <div class="relative mx-auto -mt-20 h-40 w-72 rounded-lg text-white shadow-xl transition-transform hover:scale-110 lg:h-48 lg:w-80">
            <div class="size-full rounded-lg bg-[conic-gradient(at_bottom_right,_var(--tw-gradient-stops))] from-teal-600 via-blue-200 to-neutral-100"></div>
            <div class="absolute top-0 flex size-full flex-col justify-between p-4 sm:p-5">
                <div class="flex justify-between">
                    <div>
                        <p class="text-base font-semibold text-primary">{{ __('Account Number') }}</p>
                        <span class="font-bold tracking-wide text-xl text-primary">
                            XXX-XXXX-XX
                        </span>
                    </div>
                    <x-lucide-landmark class="w-12 text-primary" />
                </div>
                <div class="flex justify-between">
                    <div>
                        <p class="text-base font-semibold text-primary">{{ __('Routing Number') }}</p>
                        <span class="font-bold tracking-wide text-xl text-primary">
                            XXXXXXXXX
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        x-show="method === @js(MerchantType::ACH->value)"
        class="flex items-center justify-between py-4"
    >
        <p class="text-xl font-semibold text-primary">
            {{ __('Account Details') }}
        </p>
    </div>

    <div x-show="method === @js(MerchantType::ACH->value)">
        <form method="POST" x-on:submit="makePayment" autocomplete="off">
            <div class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-5">
                        <label class="block">
                            <span class="font-semibold capitalize tracking-wide text-black lg:text-base">
                                {{ __('Account Number') }}<span class="text-error">*</span>
                            </span>
                            <div
                                wire:ignore
                                class="relative mt-1.5 flex"
                            >
                                <div
                                    id="tilled-js-bank-account-number"
                                    class="form-input peer h-10 w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                                >
                                    {{-- Tilled js inject bank account number input via iframe --}}
                                </div>
                            </div>
                            @error('form.account_number')
                                <div class="mt-1">
                                    <span class="text-error text-sm+">{{ $message }}</span>
                                </div>
                            @enderror
                            <template x-if="errorMessages['account_number']">
                                <div class="mt-0.5">
                                    <span class="text-error text-sm+" x-text="errorMessages['account_number']"></span>
                                </div>
                            </template>
                        </label>
                    </div>

                    <div class="lg:col-span-5">
                        <label class="block">
                            <span class="font-semibold capitalize tracking-wide text-black lg:text-base">
                                {{ __('Routing Number') }}<span class="text-error">*</span>
                            </span>
                            <div
                                wire:ignore
                                class="relative mt-1.5 flex"
                            >
                                <div
                                    id="tilled-js-bank-routing-number"
                                    class="form-input peer h-10 w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                                >
                                    {{-- Tilled js inject bank routing number input via iframe --}}
                                </div>
                            </div>
                            <template x-if="errorMessages['routing_number']">
                                <div class="mt-0.5">
                                    <span class="text-error text-sm+" x-text="errorMessages['routing_number']"></span>
                                </div>
                            </template>
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
                                    class="form-select peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
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
                            <template x-if="errorMessages['account_type']">
                                <div class="mt-0.5">
                                    <span class="text-error text-sm+" x-text="errorMessages['account_type']"></span>
                                </div>
                            </template>
                        </label>
                    </div>
                </div>

                <x-consumer.payment.account-details :$isDisplayName />

                <label class="block">
                    <span class="font-semibold uppercase tracking-wide text-black lg:text-base">
                        {{ __('ESign') }}<span class="text-error">*</span>
                    </span>
                    <div>
                        <label class="inline-flex mt-3">
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
                                            class="underline underline-offset-2 cursor-pointer"
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
                        <template x-if="errorMessages['terms_accepted']">
                            <div class="mt-0.5">
                                <span class="text-error text-sm+" x-text="errorMessages['terms_accepted']"></span>
                            </div>
                        </template>
                    </div>
                </label>
                @if ($termsAndCondition === null)
                    <div class="flex items-center justify-center sm:justify-normal">
                        <img src="{{ asset('images/dfa.png') }}" class="w-36">
                    </div>
                @endif

                <div class="flex justify-center space-x-2 pt-4">
                    <button
                        type="submit"
                        class="btn disabled:opacity-50 space-x-2 flex items-center min-w-[7rem] bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                        x-bind:disabled="submitButtonIsDisabled"
                    >
                        <x-lucide-lock class="size-5" />
                        <span class="font-semibold text-lg">{{ __('Secure Pay') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@script
    <script>
        Alpine.data('youNegotiate', () => ({
            errorMessages: [],
            showLoader: false,
            creditCardIcon: '',
            validatedData: null,
            achDebitForm: null,
            cardForm: null,
            tilled: null,
            submitButtonIsDisabled: false,
            cardNumberIsValid: false,
            cardNumberIsEmpty: true,
            expiryIsValid: false,
            expiryIsEmpty: true,
            cvvIsValid: false,
            cvvIsEmpty: true,
            accountNumberIsValid: false,
            accountNumberIsEmpty: true,
            routingNumberIsValid: false,
            routingNumberIsEmpty: true,
            async buildTilledJsForm() {
                this.tilled = new Tilled(
                    '{{ config('services.merchant.tilled_publishable_key') }}',
                    '{{ $consumer->subclient?->tilled_merchant_account_id ?? $consumer->company->tilled_merchant_account_id }}',
                    { sandbox: @js(config('services.merchant.tilled_sandbox_enabled')) }
                )

                const fieldOptions = {
                    styles: {
                        base: {
                            fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif, “Apple Color Emoji”, “Segoe UI Emoji”, “Segoe UI Symbol”, “Noto Color Emoji”',
                            fontSize: '15px',
                            letterSpacing: '1px',
                            "::placeholder": {
                                color: '#64748b',
                                fontWeight: "150",
                            }
                        },
                        invalid: {
                            color: 'red'
                        },
                        valid: {
                            color: '#2563eb',
                        }
                    }
                }

                if (this.method === @js(MerchantType::CC->value)) {
                    document.getElementById('tilled-js-credit-card-number').innerHTML = ''
                    document.getElementById('tilled-js-credit-card-expiry').innerHTML = ''
                    document.getElementById('tilled-js-credit-card-cvv').innerHTML = ''

                    this.cardForm = await this.tilled.form({payment_method_type: 'card'})
                    const cardNumberField = this.cardForm.createField('cardNumber', {...fieldOptions, placeholder: 'Card Number'}).inject('#tilled-js-credit-card-number')
                    const expiryField = this.cardForm.createField('cardExpiry', fieldOptions).inject('#tilled-js-credit-card-expiry')
                    const cvvField = this.cardForm.createField('cardCvv', {...fieldOptions, placeholder: 'Cvv/Cvc'}).inject('#tilled-js-credit-card-cvv')
                    cardNumberField.on('change', (event) => {
                        this.cardNumberIsValid = event.valid
                        this.cardNumberIsEmpty = event.empty
                        const cardBrand = event.brand
                        switch (cardBrand) {
                            case 'amex':
                                this.creditCardIcon = '{{ asset('images/payment-svgs/cc-amex.svg') }}'
                                break;
                            case 'mastercard':
                                this.creditCardIcon = '{{ asset('images/payment-svgs/cc-mastercard.svg') }}'
                                break;
                            case 'visa':
                                this.creditCardIcon = '{{ asset('images/payment-svgs/cc-visa.svg') }}'
                                break;
                            case 'discover':
                                this.creditCardIcon = '{{ asset('images/payment-svgs/cc-discover.svg') }}'
                                break;
                            case 'diners':
                                this.creditCardIcon = '{{ asset('images/payment-svgs/cc-diners.svg') }}'
                                break;
                            case 'jcb':
                                this.creditCardIcon = '{{ asset('images/payment-svgs/cc-jcb.svg') }}'
                                break;
                            default:
                                this.creditCardIcon = ''
                        }
                    })

                    expiryField.on('change', (event) => {
                        this.expiryIsValid = event.valid
                        this.expiryIsEmpty = event.empty
                    })

                    cvvField.on('change', (event) => {
                        this.cvvIsValid = event.valid
                        this.cvvIsEmpty = event.empty
                    })

                    await this.cardForm.build()
                }

                if (this.method === @js(MerchantType::ACH->value)) {
                    document.getElementById('tilled-js-bank-account-number').innerHTML = ''
                    document.getElementById('tilled-js-bank-routing-number').innerHTML = ''

                    this.achDebitForm = await this.tilled.form({payment_method_type: 'ach_debit'})
                    const bankAccountNumber =  this.achDebitForm.createField('bankAccountNumber', {...fieldOptions, placeholder: 'Account Number'}).inject('#tilled-js-bank-account-number')
                    const bankRoutingNumber = this.achDebitForm.createField('bankRoutingNumber', {...fieldOptions, placeholder: 'Routing Number'}).inject('#tilled-js-bank-routing-number')

                    bankAccountNumber.on('change', (event) => {
                        console.log(event.empty)
                        this.accountNumberIsValid = event.valid
                        this.accountNumberIsEmpty = event.empty
                    })

                    bankRoutingNumber.on('change', (event) => {
                        this.routingNumberIsValid = event.valid
                        this.routingNumberIsEmpty = event.empty
                    })

                    this.achDebitForm.build()
                }
            },
            async init() {
                await this.buildTilledJsForm()

                this.$watch('method', async () => {
                    this.achDebitForm?.teardown()
                    this.cardForm?.teardown()
                    await this.buildTilledJsForm()
                    this.errorMessages = []
                })
            },

            addressValidationMessage() {
                this.errorMessages = []
                const formFields = [
                    {
                        key:'address',
                        message:  @js(__('Enter address')),
                    },
                    {
                        key:'state',
                        message:@js(__('Enter state')),
                    },
                    {
                        key:'city',
                        message:  @js(__('Enter city')),
                    },
                    {
                        key:'zip',
                        message:  @js(__('Enter zip code')),
                    },
                ];

                formFields.forEach(({ key, message }) => {
                    if (this.$wire.form[key] === '') {
                        this.errorMessages[key] = message
                    }
                })
            },
            validateCardDetails() {
                this.addressValidationMessage()

                if (this.cardNumberIsEmpty || !this.cardNumberIsValid) {
                    this.errorMessages['card_number'] = this.cardNumberIsEmpty
                        ? @js(__('Enter credit card number.'))
                        : @js(__('Enter valid card number.'));
                }

                if (this.expiryIsEmpty || !this.expiryIsValid) {
                    this.errorMessages['expiry'] = this.expiryIsEmpty
                        ? @js(__('Enter expiry date.'))
                        : @js(__('Enter valid expiry date.'));
                }

                if (this.cvvIsEmpty || !this.cvvIsValid) {
                    this.errorMessages['cvv'] = this.cvvIsEmpty
                        ? @js(__('Enter cvv.'))
                        : @js(__('Enter valid cvv.'));
                }

                if (this.$wire.form.is_terms_accepted === false) {
                    this.errorMessages['terms_accepted'] = @js(__('Please review and accept terms and conditions.'))
                }

                if (this.$wire.form.card_holder_name === '') {
                    this.errorMessages['card_holder_name'] = @js(__('Enter card holder name'))
                }

                if (Object.keys(this.errorMessages).length !== 0) {
                    return null
                }

                return this.validatedTilledData()
            },

            validateACHDetails() {
                this.addressValidationMessage()

                if (this.$wire.form.account_type === '') {
                    this.errorMessages['account_type'] = @js( __('Select type.'))
                }

                if (this.accountNumberIsEmpty || !this.accountNumberIsValid) {
                    this.errorMessages['account_number'] = this.accountNumberIsEmpty
                        ? @js(__('Enter account number.'))
                        : @js(__('Enter valid account number.'));
                }

                if (this.routingNumberIsEmpty || !this.routingNumberIsValid) {
                    this.errorMessages['routing_number'] = this.routingNumberIsEmpty
                        ? @js(__('Enter routing number.'))
                        : @js(__('Enter valid routing number.'));
                }

                if (this.$wire.form.is_terms_accepted === false) {
                    this.errorMessages['terms_accepted'] = @js(__('Please agree to the terms and conditions'))
                }

                if (Object.keys(this.errorMessages).length !== 0) {
                    return null
                }

                return this.validatedTilledData()
            },

            validatedTilledData() {
                return {
                    method: this.$wire.form.method,
                    first_name: this.$wire.form.first_name,
                    last_name: this.$wire.form.last_name,
                    address: this.$wire.form.address,
                    city: this.$wire.form.city,
                    state: this.$wire.form.state,
                    zip: this.$wire.form.zip,
                    account_type: this.$wire.form.account_type,
                    card_holder_name: this.$wire.form.card_holder_name
                }
            },
            makeCCPayment() {
                this.validatedData = this.validateCardDetails()
                if (this.validatedData === null) {
                    this.showLoader = false
                    this.submitButtonIsDisabled = false
                    return
                }

                const payload = {
                    type: 'card',
                    billing_details: {
                        name: this.validatedData.card_holder_name,
                        address: {
                            country: 'US',
                            zip: this.validatedData.zip,
                            state: this.validatedData.state,
                            city: this.validatedData.city,
                            street: this.validatedData.address,
                        }
                    }
                }

                if (this.cardNumberIsValid && this.expiryIsValid && this.cvvIsValid) {
                    this.tilled.createPaymentMethod(payload).then(
                        async (paymentMethod) => {
                            this.$wire.form.payment_method_id = paymentMethod.id
                            this.$wire.form.tilled_response = paymentMethod
                            await this.$wire.makePayment()
                            this.submitButtonIsDisabled = false
                            this.showLoader = false
                        },
                        (error) => {
                            this.submitButtonIsDisabled = false
                            this.showLoader = false
                            console.error(error)
                            this.$notification({ text: '{{ __('Invalid payment details, please try again.') }}', variant: 'error' })
                        }
                    )
                }
            },
            makeACHPayment() {
                this.validatedData = this.validateACHDetails()
                if (this.validatedData === null) {
                    this.showLoader = false
                    this.submitButtonIsDisabled = false
                    return
                }

                const payload = {
                    type: 'ach_debit',
                    ach_debit: {
                        account_holder_name: this.$wire.isDisplayName
                            ? this.validatedData.first_name.toString().trim()
                            : @js($consumer->first_name).toString(),
                        account_type: this.validatedData.account_type.toString(),
                    },
                    billing_details: {
                        address: {
                            country: 'US',
                            zip: this.validatedData.zip,
                            state: this.validatedData.state,
                            city: this.validatedData.city,
                            street: this.validatedData.address,
                        }
                    }
                }

                this.tilled.createPaymentMethod(payload).then(
                    async (paymentMethod) => {
                        this.$wire.form.payment_method_id = paymentMethod.id
                        this.$wire.form.tilled_response = paymentMethod
                        await this.$wire.makePayment()
                        this.submitButtonIsDisabled = false
                        this.showLoader = false
                    },
                    (error) => {
                        this.submitButtonIsDisabled = false
                        this.showLoader = false
                        console.error(error)
                        this.$notification({ text: '{{ __('Invalid payment details, please try again.') }}', variant: 'error' })
                    }
                )
            },
            async makePayment() {
                this.submitButtonIsDisabled = true

                this.$event.preventDefault()
                this.showLoader = true

                if (this.method === @js(MerchantType::CC->value)) {
                    await this.makeCCPayment()
                }

                if (this.method === @js(MerchantType::ACH->value)) {
                    await this.makeACHPayment()
                }
            }
        }))
    </script>
@endscript
