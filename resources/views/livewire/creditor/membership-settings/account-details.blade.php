@use('App\Enums\BankAccountType')

<div>
    @assets
        <script src="https://js.tilled.com/v2"></script>
    @endassets

    <div
        x-data="accountDetails"
        class="relative mx-auto h-40 w-full sm:w-72 rounded-lg text-white shadow-xl"
    >
        <div class="size-full rounded-lg bg-gradient-to-r from-primary to-sky-300"></div>
        <div class="absolute top-0 flex size-full flex-col justify-between p-4 sm:p-5">
            <div class="flex justify-between">
                <div>
                    <span class="font-medium">{{ __('Card Holder Name') }}</span>
                    <p class="font-bold uppercase tracking-wide">{{ $first_name . ' ' . $last_name }}</p>
                </div>
                <div>
                    <x-dialog wire:model="accountDetailsDialogOpen">
                        <x-dialog.open>
                            <x-lucide-pencil class="size-5 cursor-pointer hover:text-primary" />
                        </x-dialog.open>

                        <x-dialog.panel
                            :heading="__('Edit Membership Billing Details')"
                        >
                            <form
                                x-on:submit.prevent="updateTheCreditCardDetails"
                                autocomplete="off"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-x-3 mb-4">
                                    <div class="my-2 col-span-12 sm:col-span-6">
                                        <x-form.input-field
                                            wire:model="first_name"
                                            type="text"
                                            name="first_name"
                                            :label="__('First Name')"
                                            :placeholder="__('Enter First Name')"
                                            class="w-full"
                                            required
                                        />
                                        <template x-if="errorMessages['first_name']">
                                            <div class="mt-2">
                                                <span class="text-error text-sm+" x-text="errorMessages['first_name']"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="my-2 col-span-12 sm:col-span-6">
                                         <x-form.input-field
                                            wire:model="last_name"
                                            type="text"
                                            name="last_name"
                                            :label="__('Last Name')"
                                            :placeholder="__('Enter Last Name')"
                                            class="w-full"
                                            required
                                        />
                                        <template x-if="errorMessages['last_name']">
                                            <div class="mt-2">
                                                <span class="text-error text-sm+" x-text="errorMessages['last_name']"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="col-span-12 my-2">
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
                                            <div class="mt-2">
                                                <span class="text-error text-sm+" x-text="errorMessages['card_number']"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="my-2 grid grid-cols-subgrid gap-x-3 col-span-12">
                                        <div class="col-span-12 sm:col-span-6 my-2">
                                            <span class="font-semibold tracking-wide text-black lg:text-md">
                                                {{ __('Expiry') }}<span class="text-error text-base">*</span>
                                            </span>
                                            <div
                                                wire:ignore
                                                id="tilled-js-expiry"
                                                class="mt-1.5 h-9 peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2"
                                            >
                                                {{-- Tilled js inject the expiry input via iframe --}}
                                            </div>
                                            <template x-if="errorMessages['expiry']">
                                                <div class="mt-2">
                                                    <span class="text-error text-sm+" x-text="errorMessages['expiry']"></span>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="col-span-12 sm:col-span-6 my-2">
                                            <span class="font-semibold tracking-wide text-black lg:text-md">
                                                {{ __('CVV') }}<span class="text-error text-base">*</span>
                                            </span>
                                            <div
                                                wire:ignore
                                                id="tilled-js-cvv"
                                                class="mt-1.5 h-9 peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2"
                                            >
                                                {{-- Tilled js inject the cvv via iframe --}}
                                            </div>
                                            <template x-if="errorMessages['cvv']">
                                                <div class="mt-2">
                                                    <span class="text-error text-sm+" x-text="errorMessages['cvv']"></span>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="grid grid-cols-1 col-span-12 gap-x-3 mb-2 text-black space-x-1 mt-3">
                                            <label class="inline-flex space-x-2 items-center">
                                                <div class="flex gap-2 items-start">
                                                    <div class="shrink-0">
                                                        <input
                                                            type="checkbox"
                                                            x-model="acceptTerms"
                                                            class="form-checkbox is-basic size-4 sm:size-4.5 my-1 rounded border-slate-400/70 checked:bg-primary hover:border-primary focus:border-primary"
                                                        >
                                                    </div>
                                                    <div>
                                                        <span>
                                                            {{ __('By checking this box, I acknowledge that I have read, understand, and agree to these') }}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            x-on:click.stop="isTermsDialogOpen = true"
                                                            class="font-bold text-primary hover:underline"
                                                        >
                                                            {{ __('Terms and Conditions') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </label>
                                            <template x-if="errorMessages['acceptTerms']">
                                                <div class="mt-2">
                                                    <span class="text-error text-sm+" x-text="errorMessages['acceptTerms']"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right space-x-2">
                                    <x-dialog.close>
                                        <x-form.default-button type="button">
                                            {{ __('Cancel') }}
                                        </x-form.default-button>
                                    </x-dialog.close>
                                    <x-form.button
                                        type="submit"
                                        variant="primary"
                                        class="disabled:opacity-50 border focus:border-primary-focus"
                                        x-bind:disabled="disableSubmit"
                                    >
                                        {{ __('Update') }}
                                    </x-form.button>
                                </div>
                            </form>
                        </x-dialog.panel>
                    </x-dialog>
                </div>
            </div>

            <div class="flex justify-between my-3">
                <div>
                    <p class="font-medium">{{ __('Card Number') }}</p>
                    <p class="font-bold uppercase tracking-wide">*** *** *** {{ $last_four_digit_of_card_number }}</p>
                </div>
                <div>
                    <p class="font-medium">{{ __('Expiry') }}</p>
                    <p class="font-bold uppercase tracking-wide">{{ $expiry }}</p>
                </div>
            </div>
            <x-dialog x-model="isTermsDialogOpen">
                <x-dialog.panel size="2xl" class="h-80">
                    <x-slot name="heading">
                        <span class="text-xs sm:text-base lg:text-xl">{{ __('TERMS AND CONDITIONS OF MEMBERSHIP') }}</span>
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
                        <x-form.default-button
                            type="button"
                            x-on:click.stop="isTermsDialogOpen = false"
                            class="mt-3"
                        >
                            {{ __('Close') }}
                        </x-form.default-button>
                    </x-slot>
                </x-dialog.panel>
            </x-dialog>
        </div>
    </div>

    @script
        <script>
            Alpine.data('accountDetails', () => {
                return {
                    errorMessages: [],
                    disableSubmit: false,
                    creditCardIcon: '',
                    tilledForm: '',
                    cardForm: '',
                    cardNumberIsValid: false,
                    cardNumberIsEmpty: true,
                    expiryIsValid: false,
                    expiryIsEmpty: true,
                    cvvIsValid: false,
                    cvvIsEmpty: true,
                    isTermsDialogOpen: false,
                    acceptTerms: false,
                    async init() {
                        this.tilledForm = new Tilled(
                            @js(config('services.merchant.tilled_publishable_key')),
                            @js(config('services.merchant.tilled_merchant_account_id')),
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
                    updateTheCreditCardDetails() {
                        this.errorMessages = []
                        if (this.$wire.first_name === '') {
                            this.errorMessages['first_name'] = @js(__('Please add your first name.'))
                        }

                        if (this.$wire.last_name === '') {
                            this.errorMessages['last_name'] = @js(__('Please add your last name.'))
                        }

                        if (!this.acceptTerms) {
                            this.errorMessages['acceptTerms'] = @js(__('You must accept the Terms and Conditions.'))
                        }

                        this.disableSubmit = true

                        const payload = {
                            type: 'card',
                            billing_details: {
                                name: this.$wire.first_name.trim() + ' ' + this.$wire.last_name.trim(),
                                address: {
                                    zip: this.$wire.zip,
                                    country: 'US',
                                },
                            }
                        }

                        if (this.cardNumberIsValid && this.expiryIsValid && this.cvvIsValid) {
                            this.tilledForm.createPaymentMethod(payload)
                                .then(async (paymentMethod) => {
                                    this.$wire.tilled_response = paymentMethod
                                    await this.$wire.updateDetails()
                                    this.disableSubmit = false
                                    this.$dialog.close()
                                }).catch((failed) => {
                                    this.disableSubmit = false
                                    this.$dialog.close()
                                    this.$notification({ text: '{{ __('Our merchant processor is having difficulties processing your payment. Please try again for email us at help@youengotiate.com! Our team is available 24/7!') }}', variant: 'error' })
                                })

                            return
                        }

                        this.disableSubmit = false

                        if (this.cardNumberIsEmpty || !this.cardNumberIsValid) {
                            this.errorMessages['card_number'] = this.cardNumberIsEmpty
                                ? @js(__('validation.required', ['attribute' => __('card number')]))
                                : @js(__('Please enter valid card number.'));
                        }

                        if (this.expiryIsEmpty || !this.expiryIsValid) {
                            this.errorMessages['expiry'] = this.expiryIsEmpty
                                ? @js(__('validation.required', ['attribute' => __('expiry date')]))
                                : @js(__('Please enter valid expiry date.'));
                        }

                        if (this.cvvIsEmpty || !this.cvvIsValid) {
                            this.errorMessages['cvv'] = this.cvvIsEmpty
                                ? @js(__('validation.required', ['attribute' => __('CVV')]))
                                : @js(__('Please enter valid cvv.'));
                        }
                    },
                    destroy() {
                        this.cardForm?.teardown()
                    }
                }
            })
        </script>
    @endscript
</div>
