@use('Illuminate\Support\Number')
@use('App\Enums\BankAccountType')

@assets
    <script src="https://js.tilled.com/v2"></script>
@endassets

<div x-data="membershipBillingDetails">
    <div class="grid grid-cols-1 lg:grid-cols-12">
        <div class="sm:col-span-8 order-2 lg:order-1">
            <x-account-profile.card
                id="billing-details-form"
                x-on:close-confirm-box.window="closeConfirmBox"
            >
                <x-slot name="actionButtons"></x-slot>
                <x-loader x-show="showLoader" />

                <div x-show="$wire.tilledErrorMessage !== ''" class="w-full sm:w-2/3 mx-auto">
                    <div class="alert flex rounded-lg border border-error/30 bg-error/10 py-4 px-4 text-error sm:px-5 items-center">
                        <x-lucide-triangle-alert class="size-5" />
                        <div class="flex-grow ml-2">
                            <span x-text="$wire.tilledErrorMessage"></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-x-3 w-full xl:w-2/3 mx-auto">
                    <div class="mt-5 col-span-12 sm:col-span-6">
                        <x-form.input-field
                            type="text"
                            x-model="first_name"
                            wire:model="form.first_name"
                            :label="__('First Name')"
                            name="form.first_name"
                            class="w-full"
                            :placeholder="__('Enter First Name')"
                            required
                        />
                        <template x-if="errorMessages['first_name']">
                            <div class="mt-0.5">
                                <span class="text-error text-sm+" x-text="errorMessages['first_name']"></span>
                            </div>
                        </template>
                    </div>
                    <div class="mt-5 col-span-12 sm:col-span-6">
                        <x-form.input-field
                            type="text"
                            x-model="last_name"
                            wire:model="form.last_name"
                            :label="__('Last Name')"
                            name="form.last_name"
                            class="w-full"
                            :placeholder="__('Enter Last Name')"
                            required
                        />
                        <template x-if="errorMessages['last_name']">
                            <div class="mt-0.5">
                                <span class="text-error text-sm+" x-text="errorMessages['last_name']"></span>
                            </div>
                        </template>
                    </div>
                    <div class="mt-5 col-span-12 sm:col-span-6">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Card Number') }}<span class="text-error text-base">*</span>
                        </span>
                        <label class="relative flex">
                            <div
                                wire:ignore
                                id="tilled-js-card-number"
                                class="form-input mt-1.5 h-9 peer w-full rounded-l-lg border-r-0 border border-slate-300 bg-transparent px-3 py-2"
                            >
                                {{-- Tilled js inject the card number input via iframe --}}
                            </div>
                            <div class="pointer-events-none flex w-10 mt-1.5 h-9 border border-slate-300 border-l-0 rounded-r-lg items-center px-1">
                                <template x-if="creditCardIcon">
                                    <img
                                        x-bind:src="creditCardIcon"
                                        class="size-12"
                                        alt="credit-card-icon"
                                    >
                                </template>
                            </div>
                        </label>
                        <template x-if="errorMessages['card_number']">
                            <div class="mt-1">
                                <span class="text-error text-sm+" x-text="errorMessages['card_number']"></span>
                            </div>
                        </template>
                    </div>
                    <div class="mt-5 col-span-12 sm:col-span-3">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Exp.') }}<span class="text-error text-base font-semibold">*</span>
                        </span>
                        <div
                            wire:ignore
                            id="tilled-js-expiry"
                            class="mt-1.5 h-9 peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2"
                        >
                            {{-- Tilled js inject the expiry input via iframe --}}
                        </div>
                        <template x-if="errorMessages['expiry']">
                            <div class="mt-0.5">
                                <span class="text-error text-sm+" x-text="errorMessages['expiry']"></span>
                            </div>
                        </template>
                    </div>
                    <div class="mt-5 col-span-12 sm:col-span-3">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('CVV') }}<span class="text-error text-base font-semibold">*</span>
                        </span>
                        <div
                            wire:ignore
                            id="tilled-js-cvv"
                            class="mt-1.5 h-9 peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2"
                        >
                            {{-- Tilled js inject the cvv via iframe --}}
                        </div>
                        <template x-if="errorMessages['cvv']">
                            <div class="mt-0.5">
                                <span class="text-error text-sm+" x-text="errorMessages['cvv']"></span>
                            </div>
                        </template>
                    </div>
                    <div class="grid grid-cols-1 col-span-12 gap-x-3 mt-3">
                        <x-smarty-address
                            class="mt-2 md:!grid-cols-2"
                            :blockTitle="__('Address Details')"
                            required
                        />
                    </div>
                    <div class="grid grid-cols-1 col-span-12 gap-x-3 mb-2 text-black space-x-1">
                        <label class="inline-flex space-x-2 items-center">
                            <x-dialog>
                                <div class="flex gap-2 items-start">
                                    <div class="shrink-0">
                                        <input
                                            wire:model.boolean="form.acceptTermsAndConditions"
                                            type="checkbox"
                                            class="form-checkbox is-basic size-4 sm:size-4.5 my-1 rounded border-slate-400/70 checked:bg-primary hover:border-primary focus:border-primary"
                                        >
                                        @error('form.acceptTermsAndConditions')
                                            <div class="mt-0.5">
                                                <span class="text-error text-sm+">
                                                    {{ $message }}
                                                </span>
                                            </div>
                                        @enderror
                                    </div>
                                    <div>
                                        <span>
                                            {{ __('By checking this box, I acknowledge that I have read, understand, and agree to these') }}
                                        </span>
                                        <x-dialog.open>
                                            <button
                                                type="button"
                                                variant="success"
                                                class="font-bold text-primary hover:underline"
                                            >
                                                {{ __('Terms and Conditions') }}
                                            </button>
                                        </x-dialog.open>
                                    </div>
                                </div>
                                <x-dialog.panel
                                    size="2xl"
                                    class="h-80"
                                >
                                    <x-slot name="heading">
                                        {{ __('TERMS AND CONDITIONS OF MEMBERSHIP') }}
                                    </x-slot>
                                    <div class="m-2 text-black">
                                        <div class="p-1 mt-2">
                                            <h3 class="text-lg font-semibold my-3">1. Membership Agreement</h3>
                                            <p>By checking the box below, you ("Member") agree to these Terms and Conditions ("Agreement") with www.younegotiate.com ("YN"). This Agreement governs your membership and use of YN's services.</p>

                                            <h3 class="text-lg font-semibold mt-5 mb-3">2. Membership Plans</h3>
                                            <p>YN offers various membership plans ("Plans") with different fees and terms. You select your preferred Plan and agree to pay the associated fees.</p>

                                            <h3 class="text-lg font-semibold mt-5 mb-3">3. Payment Terms</h3>
                                            <p>a. <span class="font-semibold">Fees:</span> You agree to pay monthly or annual licensing fees, percentage-based fees on consumer payments, and/or communication/processing transaction fees, as applicable to your chosen Plan.</p>
                                            <p>b. <span class="font-semibold">Billing:</span>  YN will bill your credit card on file for all fees.</p>
                                            <p>c. <span class="font-semibold">Disputes:</span> Any billing disputes must be submitted to <a href="mailto:help@younegotiate.com" target="_blank" class="text-primary hover:underline">help@younegotiate.com</a> within 45 days.</p>

                                            <h3 class="text-lg font-semibold mt-5 mb-3">4. Membership Benefits</h3>
                                            <p>YN will provide access to its platform and services as described on the website.</p>

                                            <h3 class="text-lg font-semibold mt-5 mb-3">5. Term and Termination</h3>
                                            <p>a. <span class="font-semibold">Term:</span> This Agreement begins upon membership activation and continues until terminated.</p>
                                            <p>b. <span class="font-semibold">Termination:</span> Either party may terminate this Agreement with written notice (email accepted).</p>

                                            <h3 class="text-lg font-semibold mt-5 mb-3">6. Intellectual Property</h3>
                                            <p>YN retains all rights to its intellectual property.</p>

                                            <h3 class="text-lg font-semibold mt-5 mb-3">7. Warranty Disclaimer</h3>
                                            <p>YN services are provided "as-is" without warranties.</p>

                                            <h3 class="text-lg font-semibold mt-5 mb-3">8. Limitation of Liability</h3>
                                            <p>YN's liability is limited to the amount of fees paid.</p>

                                            <h3 class="text-lg font-semibold mt-5 mb-3">9. Indemnification</h3>
                                            <p>You agree to indemnify YN against all claims.</p>

                                            <h3 class="text-lg font-semibold mt-5 mb-3">10. Governing Law</h3>
                                            <p>This Agreement is governed by [State/Country] laws.</p>

                                            <h3 class="text-lg font-semibold mt-5 mb-3">11. Changes to Terms</h3>
                                            <p>YN reserves the right to update these Terms.</p>
                                        </div>
                                    </div>
                                    <x-slot name="footer" class="mt-3">
                                        <x-dialog.close>
                                            <x-form.default-button
                                                type="button"
                                                class="mt-3"
                                            >
                                                {{ __('Close') }}
                                            </x-form.default-button>
                                        </x-dialog.close>
                                    </x-slot>
                                </x-dialog.panel>
                            </x-dialog>
                        </label>
                        <template x-if="errorMessages['acceptTermsAndConditions']">
                            <div class="mt-0.5">
                                <span class="text-error text-sm+" x-text="errorMessages['acceptTermsAndConditions']"></span>
                            </div>
                        </template>
                    </div>

                    <div class="col-span-12 xl:w-2/3 mx-auto">
                        <div class="flex items-center gap-x-5">
                            <button
                                type="button"
                                wire:click="$dispatchTo('creditor.account-profile.index-page', 'previous')"
                                class="btn space-x-2 mt-4 w-full bg-slate-150 font-medium text-nowrap text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                            >
                                {{ __('View Other Plans') }}
                            </button>
                            <button
                                type="button"
                                x-on:click="validateCardDetails"
                                class="btn w-full mt-4 space-x-2 space-x-reverse bg-success font-medium text-nowrap text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                                form="billing-details-form"
                            >
                                <span>{{ __('Pay Now') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </x-account-profile.card>
        </div>

        <div class="sm:col-span-4 px-4 my-3 w-full sm:w-3/5 sm:mx-auto lg:w-auto lg:mx-0 xl:w-4/5 2xl:w-2/3 order-1 lg:order-2">
            <div class="rounded-xl border-4 border-primary">
                <div class="flex flex-col justify-between h-full rounded-xl bg-slate-50 p-4 text-center">
                    <div class="mt-4">
                        <h4 class="text-xl font-semibold text-slate-700">
                            {{ $companyMembership->membership->name }}
                        </h4>
                        <span
                            x-tooltip.placement.bottom="'{{ $companyMembership->membership->description }}'"
                            class="mt-2 line-clamp-2 hover:underline hover:cursor-pointer"
                        >
                            {{ $companyMembership->membership->description }}
                        </span>
                    </div>
                    <div class="mt-3 flex justify-center items-center">
                        <span class="text-3xl tracking-tight text-primary">
                            {{ Number::currency((float) $companyMembership->membership->price) }}
                        </span> &nbsp;/ {{ $companyMembership->membership->frequency->displayName() }}
                    </div>
                    <div class="mt-3 space-y-1 text-left">
                        <div class="flex items-start space-x-reverse">
                            <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                <x-heroicon-m-check class="size-5 text-success" />
                            </div>
                            <span class="font-medium text-black">
                                {{ __('Upload account limit :accounts', ['accounts' =>
                                $companyMembership->membership->upload_accounts_limit]) }}
                            </span>
                        </div>
                        <div class="flex items-start space-x-reverse">
                            <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                <x-heroicon-m-check class="size-5 text-success" />
                            </div>
                            <span class="font-medium text-black">
                                {{ __(':fees fee on all consumer payments', ['fees' =>
                                Number::percentage($companyMembership->membership->fee, 2)]) }}
                            </span>
                        </div>
                        @foreach ($enableFeatures as $name => $value)
                            <div class="flex items-start space-x-reverse">
                                <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                    <x-heroicon-m-check class="size-5 text-success" />
                                </div>
                                <span class="font-medium text-black">
                                    {{ $value }}
                                </span>
                            </div>
                        @endforeach
                        @foreach ($disableFeatures as $name => $value)
                            <div class="flex items-start space-x-reverse">
                                <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                    <x-heroicon-m-x-mark class="size-5 text-error" />
                                </div>
                                <span class="font-medium">
                                    {{ $value }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <div class="flex justify-between space-x-2 py-3 px-2">
                    <img src="{{ asset('images/secured.png') }}" alt="secure_payment">
                </div>
            </div>
        </div>
    </div>

    <x-dialog wire:model="displaySuccessModal">
        <x-dialog.panel confirm-box>
            <x-slot name="svg">
                <x-emoji-happy-smile class="inline size-18" />
            </x-slot>

            <x-slot name="heading">
                <span class="text-xl font-medium">{{ __("Your membership payment has been processed!") }}</span>
            </x-slot>

            <x-slot name="buttons">
                <a
                    href="{{ route('home') }}"
                    class="w-36 uppercase btn select-none text-white bg-success hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                >
                    {{ __('Get Started') }}
                </a>
            </x-slot>
        </x-dialog.panel>
    </x-dialog>
</div>

@script
    <script>
        Alpine.data('membershipBillingDetails', () => {
            return {
                errorMessages: [],
                showLoader: false,
                first_name: '',
                last_name: '',
                cardNumberIsValid: false,
                cardNumberIsEmpty: true,
                expiryIsValid: false,
                expiryIsEmpty: true,
                cvvIsValid: false,
                cvvIsEmpty: true,
                tilledForm: '',
                cardForm: '',
                creditCardIcon: '',

                async init() {
                    this.$watch('$wire.displaySuccessModal', () => {
                        if (this.$wire.displaySuccessModal === false) {
                            this.$wire.displaySuccessModal = true
                        }
                    })

                    this.tilledForm = new Tilled(
                        '{{ config('services.merchant.tilled_publishable_key') }}',
                        '{{ config('services.merchant.tilled_merchant_account_id') }}',
                        { sandbox: @js(config('services.merchant.tilled_sandbox_enabled')) }
                    )

                    this.cardForm = await this.tilledForm.form({ payment_method_type: 'card' })

                    const fieldOptions = {
                        styles: {
                            base: {
                                fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif, “Apple Color Emoji”, “Segoe UI Emoji”, “Segoe UI Symbol”, “Noto Color Emoji”',
                                fontSize: '16px',
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

                    const cardNumberField = this.cardForm.createField('cardNumber', {...fieldOptions, placeholder: 'Enter Card Number'}).inject('#tilled-js-card-number')
                    const expiryField = this.cardForm.createField('cardExpiry', fieldOptions).inject('#tilled-js-expiry')
                    const cvvField = this.cardForm.createField('cardCvv', {...fieldOptions, placeholder: 'Enter CVV'}).inject('#tilled-js-cvv')

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
                },
                 closeConfirmBox() {
                    this.showLoader = false
                },
                validateCardDetails() {
                    this.errorMessages = []
                    const formFields = [
                        {
                            key:'first_name',
                            message:  @js(__('validation.required,min10', ['attribute' => __('first name')])),
                            validations: [
                                {
                                    rule: this.$wire.form.first_name.length < 2 || this.$wire.form.first_name.length > 20,
                                    message: @js(__('Enter first name (2-20 characters)', ['attribute' => 'first name']))
                                },
                                {
                                    rule: !/^[A-Za-z]*$/.test(this.$wire.form.first_name),
                                    message: @js(__('The :attribute field should only contain alphabetic characters', ['attribute' => 'first name']))
                                }
                            ]
                        },
                        {
                            key:'last_name',
                            message:  @js(__('validation.required', ['attribute' => __('last name')])),
                            validations: [
                                {
                                    rule: this.$wire.form.last_name.length < 2 || this.$wire.form.last_name.length > 30,
                                    message: @js(__('Enter last name (2-30 characters)', ['attribute' => 'last name']))
                                },
                                {
                                    rule: !/^[A-Za-z]*$/.test(this.$wire.form.last_name),
                                    message: @js(__('The :attribute field should only contain alphabetic characters', ['attribute' => 'last name']))
                                }
                            ]
                        },
                        {
                            key:'address',
                            message:  @js(__('validation.required', ['attribute' => __('address')])),
                            validations: [
                                {
                                    rule: !/[A-Za-z]/.test(this.$wire.form.address),
                                    message: @js(__('The :attribute field least one alphabetic characters', ['attribute' => 'address']))
                                },
                                {
                                    rule: /\s{2,}/.test(this.$wire.form.address),
                                    message: @js(__('The :attribute field must not contain multiple consecutive spaces', ['attribute' => 'address']))
                                },
                                {
                                    rule: this.$wire.form.address === '',
                                    message: @js(__('Enter address'))
                                }
                            ]
                        },
                        {
                            key:'state',
                            message:@js(__('Enter state')),
                            validations: [
                                {
                                    rule: this.$wire.from.state === '',
                                    message: @js(__('Enter state'))
                                }
                            ]
                        },
                        {
                            key:'city',
                            message:  @js(__('Enter city')),
                            validations: [
                                {
                                    rule: !/^[A-Za-z]+( [A-Za-z]+)*$/.test(this.$wire.form.city),
                                    message: @js(__('The :attribute field should only contain alphabetic characters and single spaces between words', ['attribute' => 'city']))
                                },
                                {
                                    rule: this.$wire.form.city === '',
                                    message: @js(__('Enter city'))
                                },
                            ]
                        },
                        {
                            key:'zip',
                            message:  @js(__('Enter zip code')),
                            validations: [
                                {
                                    rule: this.$wire.form.zip.length !== 5,
                                    message: @js(__('validation.size.numeric', ['attribute' => __('zip'), 'size' => '5']))
                                },
                                {
                                    rule: !/^\d+$/.test(this.$wire.form.zip),
                                    message: @js(__('validation.numeric', ['attribute' => __('zip')]))
                                },
                                {
                                    rule: this.$wire.form.zip === '',
                                    message: @js(__('Enter zip code'))
                                }
                            ]
                        },
                    ];

                    formFields.forEach(({ key, message, validations }) => {
                        if (this.$wire.form[key] === '') {
                            this.errorMessages[key] = message
                        }

                        if (validations) {
                            validations.forEach(({ rule, message }) => {
                                if (rule) {
                                    this.errorMessages[key] = message;
                                }
                            });
                        }
                    })

                    if (this.cardNumberIsEmpty || !this.cardNumberIsValid) {
                        this.errorMessages['card_number'] = this.cardNumberIsEmpty
                            ? @js(__('Enter credit card number'))
                            : @js(__('Enter valid card number.'));
                    }

                    if (this.expiryIsEmpty || !this.expiryIsValid) {
                        this.errorMessages['expiry'] = this.expiryIsEmpty
                            ? @js(__('Enter expiry date'))
                            : @js(__('Enter valid expiry date.'));
                    }

                    if (this.cvvIsEmpty || !this.cvvIsValid) {
                        this.errorMessages['cvv'] = this.cvvIsEmpty
                            ? @js(__('Enter cvv'))
                            : @js(__('Enter valid cvv.'));
                    }

                    if (! this.$wire.form.acceptTermsAndConditions) {
                        this.errorMessages['acceptTermsAndConditions'] = @js(__('Please agree to the terms and conditions'))
                    }

                    if (Object.keys(this.errorMessages).length === 0) {
                        this.confirmedToStoreMembershipBillingDetails();
                    }
                },

                async confirmedToStoreMembershipBillingDetails() {
                    this.showLoader = true

                    const payload = {
                        type: 'card',
                        billing_details: {
                            name: this.$wire.form.first_name.trim() + ' ' + this.$wire.form.last_name.trim(),
                            address: {
                                zip: this.$wire.form.zip,
                                country: 'US',
                            },
                        }
                    }

                    if (this.cardNumberIsValid && this.expiryIsValid && this.cvvIsValid) {
                        this.tilledForm.createPaymentMethod(payload)
                            .then((paymentMethod) => {
                                this.$wire.form.tilled_response = paymentMethod
                                this.$wire.storeMembershipBillingDetails()
                                    .then(() => {
                                        this.showLoader = false;
                                    })
                            }).catch((failed) => {
                                this.$wire.tilledErrorMessage = failed
                                this.showLoader = false
                            })
                        return
                    }

                    this.showLoader = false
                },
                destroy() {
                    this.cardForm?.teardown()
                }
            }
        })
    </script>
@endscript
