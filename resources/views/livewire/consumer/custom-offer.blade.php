@use('App\Enums\InstallmentType')
@use('App\Enums\NegotiationType')

<div>
    <main class="w-full pb-8">
        <div class="flex items-center space-x-4 py-5 lg:py-6">
            <h2 class="font-bold text-primary text-lg lg:text-2xl">
                <span>{{ __('Let\'s Create Your Custom Offer...') }}</span>
            </h2>
        </div>

        <div
            x-data="customOffer"
            class="card text-black"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-12 sm:gap-5 lg:gap-6 p-5">
                <div class="items-center sm:col-span-6 md:col-span-5 lg:col-span-7 space-y-4 rounded-2xl border border-slate-150 p-4">
                    <div>
                        <label class="block w-full">
                            <span class="font-semibold text-base lg:text-xl">{{ __('Select Offer Type') }}</span>
                            <select
                                wire:model="form.negotiation_type"
                                class="form-select mt-3 w-full rounded-lg border border-black bg-white px-3 py-2 hover:border-slate-400 focus:border-primary"
                            >
                                @foreach (NegotiationType::cases() as $case)
                                    <option value="{{ $case->value }}">{{ $case->selectionBox() }}</option>
                                @endforeach
                            </select>
                        </label>
                        @error('form.negotiation_type')
                            <div class="mt-1">
                                <span class="text-error">
                                    {{ $message }}
                                </span>
                            </div>
                        @enderror

                        <div
                            x-show="$wire.form.negotiation_type === @js(NegotiationType::INSTALLMENT->value)"
                            x-transition:enter="transition ease-in duration-300"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-out duration-300"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90"
                            class="mt-4"
                        >
                            <label class="block w-full">
                                <span class="font-semibold text-base lg:text-xl">{{ __('Payment Frequency') }}</span>
                                <p class="text-sm pt-2">{{ __('How often would you like to make payments?') }}</p>
                                <select
                                    wire:model="form.installment_type"
                                    class="form-select mt-3 w-full rounded-lg border border-black bg-white px-3 py-2 hover:border-slate-400 focus:border-primary"
                                >
                                    <option value="">{{ __('Select Installment Type') }}</option>
                                    @foreach (InstallmentType::displaySelectionBox() as $value => $name)
                                        <option value="{{ $value }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            @error('form.installment_type')
                                <div class="mt-1">
                                    <span class="text-error">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-8">
                        <span
                            class="font-semibold text-base lg:text-xl"
                            x-text="$wire.form.negotiation_type === @js(NegotiationType::PIF->value) ? @js(__('Settlement Amount')) : @js(__('Payment Amount'))"
                        ></span>
                        <label class="mt-3 flex -space-x-px">
                            <span class="flex shrink-0 p-2 items-center justify-center rounded-l-lg border border-primary bg-primary px-3.5">
                                <span class="-mt-1 px-3 text-white font-bold">$</span>
                            </span>
                            <input
                                x-model="amount"
                                x-ref="amount"
                                x-on:input.debounce.500ms="setAmount"
                                type="text"
                                pattern="[0-9.]+"
                                class="form-input w-full rounded-r-lg border border-black bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary"
                                x-bind:placeholder="$wire.form.negotiation_type === @js(NegotiationType::PIF->value) ? @js(__('Enter Settlement Amount')) : @js(__('Enter Payment Amount'))"
                                autocomplete="off"
                            >
                        </label>
                        <div class="text-error mt-1" x-text="amountValidationError"></div>
                        @error('form.amount')
                            <div class="mt-1">
                                <span class="text-error">
                                    {{ $message }}
                                </span>
                            </div>
                        @enderror
                    </div>
                    <div class="mt-8">
                        <h3 class="text-base lg:text-lg font-medium">
                            <span>{{ __('Current Balance ') }}</span>
                            <span class="font-semibold text-primary">
                                {{ Number::currency((float) $consumer->current_balance) }}
                            </span>
                        </h3>
                        <h3 class="text-base lg:text-lg font-medium mt-3 flex items-center space-x-2">
                            <div class="flex flex-col lg:flex-row lg:items-center">
                                <template x-if="$wire.form.negotiation_type === @js(NegotiationType::PIF)">
                                    <div>
                                        <span>{{ __('Creditor Offer ') }}</span>
                                        <span class="font-semibold text-primary">
                                            {{ Number::currency((float) $minimumPifDiscountedAmount) }}
                                        </span>
                                    </div>
                                </template>
                                <template x-if="$wire.form.negotiation_type === @js(NegotiationType::INSTALLMENT)">
                                    <div>
                                        <span>{{ __('Payment Plan Balance ') }}</span>
                                        <span class="font-semibold text-primary">
                                            {{ Number::currency((float) $minimumPpaDiscountedAmount) }}
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </h3>
                        <template x-if="$wire.form.negotiation_type === @js(NegotiationType::PIF->value) && $wire.form.amount !== ''">
                            <h3 class="text-base lg:text-lg font-medium mt-2">
                                <span>{{ __('Your Offer ') }}</span>
                                <span class="font-semibold text-primary" x-text="'$' + displayAmount"/>
                            </h3>
                        </template>
                        <template x-if="showInstallmentDetails && $wire.form.negotiation_type === @js(NegotiationType::INSTALLMENT->value)">
                            <div class="mt-2">
                                <h3 class="text-lg font-medium sm:flex items-center gap-1">
                                    <p>
                                        <span>{{ __('Your offer ') }}</span>
                                        <span x-text="numberOfInstallmentMonths" class="font-semibold text-primary"></span>
                                        <span>{{ __('Payment(s) of') }}</span>
                                        <span class="font-semibold text-primary" x-text="installmentAmount"></span>
                                        <span x-show="showLastInstallmentDetails">
                                            <span>{!! __('and <span class="font-semibold text-primary">one</span> last payment of') !!}</span>
                                            <span class="font-semibold text-primary" x-text="lastMonthAmount"></span>
                                        </span>
                                    </p>
                                </h3>
                            </div>
                        </template>

                        <div class="!mt-8">
                            <label class="block w-full">
                                <span class="font-semibold text-base lg:text-xl">
                                    {{ __('Select why you are requesting a custom offer') }}
                                </span>
                                <p class="text-sm pt-2">
                                    {{ __('Help Your Creditor Understand Why You Need Custom Offer') }}
                                </p>
                                <select
                                    wire:model="form.reason"
                                    class="form-select mt-3 w-full rounded-lg border border-black bg-white px-3 py-2 hover:border-slate-400 focus:border-primary"
                                >
                                    <option value="">{{ __('Please Select...') }}</option>
                                    <option value="{{ __('Prefer not to say') }}">{{ __('Prefer not to say') }}</option>
                                    <option value="{{ __('UnEmployed') }}">{{ __('UnEmployed') }}</option>
                                    <option value="{{ __('Death in the Family') }}">{{ __('Death in the Family') }}</option>
                                    <option value="{{ __('Unexpected Expenses') }}">{{ __('Unexpected Expenses') }}</option>
                                    <option value="{{ __('Co-Signer on Loan') }}">{{ __('Co-Signer on Loan') }}</option>
                                    <option value="{{ __('Affected by Natural Disaster') }}">{{ __('Affected by Natural Disaster') }}</option>
                                    <option value="{{ __('My Industry Shutdown') }}">{{ __('My Industry Shutdown') }}</option>
                                    <option value="{{ __('I am in the hospital') }}">{{ __('I am in the hospital') }}</option>
                                    <option value="{{ __('I am on Social Security/Disability') }}">{{ __('I am on Social Security/Disability') }}</option>
                                    <option value="{{ __('Other - I just want lower Payoff') }}">{{ __('Other - I just want lower Payoff') }}</option>
                                </select>
                            </label>
                            @error('form.reason')
                                <div class="mt-1">
                                    <span class="text-error">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </div>
                    </div>


                </div>
                <div class="items-center sm:col-span-6 md:col-span-7 lg:col-span-5 space-y-4 rounded-2xl border border-slate-150 p-4">
                    <x-consumer.custom-offer.first-pay-date />
                    <div class="text-error mt-1" x-text="firstPayDateValidationError"></div>

                    <div class="mt-8">
                        <label class="block w-full">
                            <span class="font-semibold text-base lg:text-xl">{{ __('Additional Notes') }}</span>
                            <p class="text-sm pt-2">{{ __('We will send to your creditor with your offer') }}</p>
                            <textarea
                                wire:model="form.note"
                                rows="4"
                                placeholder="{{ __('Enter notes') }}"
                                class="form-textarea w-full mt-1 resize-none rounded-lg border border-black bg-transparent p-2.5 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary"
                            ></textarea>
                        </label>
                    </div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row mt-5 justify-center pb-5 sm:space-x-2 space-y-2 sm:space-y-0 items-center">
                <button
                    type="button"
                    @click="submitOffer"
                    wire:loading.attr="disabled"
                    class="btn disabled:opacity-50 bg-primary uppercase font-semibold text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 text-xs lg:text-sm w-11/12 sm:w-auto"
                >
                    {{ __('Submit my offer to creditor!') }}
                </button>
                <a
                    wire:navigate
                    href="{{ route('consumer.negotiate', ['consumer' => $consumer->id]) }}"
                    class="btn uppercase bg-slate-150 font-semibold text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 text-xs lg:text-sm w-11/12 sm:w-auto"
                >
                    {{ __('Back to my offer!') }}
                </a>
                <x-consumer.dialog x-model="displayConfirmBox">
                    <x-consumer.dialog.panel :heading="__('Custom Offer Submission')">
                        <div class="border">
                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                <h3 class="text-black">{{ __("First Payment Date") }}</h3>
                                <p class="text-primary"><span x-text="firstPayDate"></span></p>
                            </div>
                            <template x-if="$wire.form.negotiation_type === @js(NegotiationType::PIF->value)">
                                <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                    <h3 class="text-black">{{ __("Settlement Amount") }}</h3>
                                    <span class="text-primary" x-text="'$' + displayAmount"></span>
                                </div>
                            </template>
                            <template x-if="showInstallmentDetails && $wire.form.negotiation_type === @js(NegotiationType::INSTALLMENT->value)">
                                <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                    <h3 class="text-black">{{ __("Number of Installment") }}</h3>
                                    <span class="text-primary" x-text="numberOfInstallmentMonths"></span>
                                </div>
                            </template>
                            <template x-if="showInstallmentDetails && $wire.form.negotiation_type === @js(NegotiationType::INSTALLMENT->value)">
                                <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                    <h3 class="text-black">{{ __("Installment Amount") }}</h3>
                                    <span class="text-primary" x-text="installmentAmount"></span>
                                </div>
                            </template>
                            <template x-if="showLastInstallmentDetails && $wire.form.negotiation_type === @js(NegotiationType::INSTALLMENT->value)">
                                <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                    <h3 class="text-black">{{ __("Last Installment Amount") }}</h3>
                                    <span class="text-primary" x-text="lastMonthAmount"></span>
                                </div>
                            </template>
                        </div>

                        <div class="p-3 text-sm">
                            <p>{{__('You are about to submit this custom offer based on the details above. Once submitted, the offer will be sent for approval.')}}</p>
                            <p>{{__('Would you like to proceed?')}}</p>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-4">
                            <x-dialog.close>
                                <x-form.default-button
                                    type="button"
                                    class="w-full sm:w-32"
                                >
                                    {{ __('Cancel') }}
                                </x-form.default-button>
                            </x-dialog.close>
                            <x-form.button
                                wire:click="createCustomOffer"
                                wire:loading.attr="disabled"
                                wire:target="createCustomOffer"
                                type="submit"
                                variant="primary"
                                class="disabled:opacity-50"
                                @click="displayConfirmBox = false"
                            >
                                <span>{{ __('Submit Offer') }}</span>
                            </x-form.button>
                        </div>
                    </x-consumer.dialog.panel>
                </x-consumer.dialog>
            </div>

            {{-- This variable is defined for the display loader in `app-layout` --}}
            <template x-if="! visible">
                <x-consumer.dialog x-model="isOfferSent">
                    <x-consumer.dialog.panel
                        :blur="true"
                        :confirmBox="true"
                        size="2xl"
                    >
                        <x-slot name="svg">
                            <x-lucide-mail-check class="inline size-28 text-success" />
                        </x-slot>
                        <x-slot name="heading">
                            <h2 class="text-lg lg:text-2xl font-semibold text-black">{{ __('Awesome! Your Offer Was Sent to Your Creditor!') }}</h2>
                        </x-slot>
                        <x-slot name="message">
                            <div class="space-y-3 font-medium text-sm sm:text-base">
                                <p class="mt-5">{{ __('Let\'s setup the payment method so you\'re best positioned for an instant approval!') }}</p>
                                <p>{{ __('Always know we\'ll never share your payment information with your creditor or use it for anything outside of your approved and agreed upon payments.') }}</p>
                            </div>
                            <div class="mt-5">
                                <span class="text-base lg:text-xl font-bold text-center text-black">
                                    {{ __('What would you like to do now?') }}
                                </span>
                            </div>
                        </x-slot>
                        <x-slot name="buttons">
                            <div class="flex flex-col sm:flex-row gap-3 text-center justify-center">
                                <a
                                    wire:navigate
                                    href="{{ route('consumer.payment', ['consumer' => $consumer]) }}"
                                    class="btn bg-success font-semibold text-sm lg:text-base text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus"
                                >
                                    {{ __('Set up payment profile!!') }}
                                </a>
                                <a
                                    wire:navigate
                                    href="{{ route('consumer.account') }}"
                                    class="btn bg-primary font-semibold text-sm lg:text-base text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                                >
                                    {{ __('My Accounts') }}
                                </a>
                            </div>
                        </x-slot>
                    </x-consumer.dialog.panel>
                </x-consumer.dialog>
            </template>

            <x-consumer.offers.offer-accepted-dialog :$consumer />
        </div>
    </main>

    @script
        <script>
            Alpine.data('customOffer', () => ({
                isOfferSent: false,
                showInstallmentDetails: false,
                showLastInstallmentDetails: false,
                lastMonthAmount: 0,
                firstPayDateValidationError: '',
                amountValidationError: '',
                displayConfirmBox: false,
                firstPayDate: '',
                amount: '',
                displayAmount: '',
                numberOfInstallmentMonths: '',

                init() {
                    this.$wire.$watch('form.negotiation_type', () => {
                        if (this.$wire.form.negotiation_type !== @js(NegotiationType::INSTALLMENT->value)) {
                            this.$wire.form.installment_type = ''
                        }
                    })

                    this.isOfferSent = this.$wire.form.offerSent

                    this.$wire.$watch('form.offerSent', () => {
                        if (this.$wire.form.offerSent) {
                            this.isOfferSent = this.$wire.form.offerSent
                        }
                    })

                    this.$watch('isOfferSent', () => {
                        if (this.$wire.form.offerSent) {
                            this.isOfferSent = this.$wire.form.offerSent
                        }
                    })

                    this.amount = this.$wire.form.amount
                    this.$nextTick(() => {
                        this.setAmount()
                    })
                },
                setAmount() {
                    if (this.$refs.amount.value === '') {
                        this.showInstallmentDetails = false
                        this.showLastInstallmentDetails = false
                        this.$wire.form.amount = ''
                        return
                    }

                    // Ref: This regex removes all characters except numbers (0-9) and decimal points (.)
                    let amount = this.$refs.amount.value.replace(/[^0-9.]/g, '')

                    let minimumRequiredAmount = this.$wire.form.negotiation_type === @js(NegotiationType::PIF->value)
                        ? this.$wire.minimumPifDiscountedAmount
                        : this.$wire.minimumPpaDiscountedAmount

                    if (parseFloat(amount.replace(/,/g, '')) > minimumRequiredAmount) {
                        amount = minimumRequiredAmount.toFixed(2)
                        this.amount = minimumRequiredAmount.toFixed(2)
                    }

                    // Ref: This regex adds commas as thousand separators
                    this.amount = amount.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                    this.$wire.form.amount = amount
                    this.displayAmount = parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")

                    const enteredAmount = parseFloat(amount)
                    const minimumPpaDiscountedAmount = this.$wire.minimumPpaDiscountedAmount

                    this.numberOfInstallmentMonths = Math.floor(minimumPpaDiscountedAmount / enteredAmount)
                    let lastMonthAmount = minimumPpaDiscountedAmount - (this.numberOfInstallmentMonths * enteredAmount)

                    if (lastMonthAmount < 10 && lastMonthAmount > 0) {
                        this.numberOfInstallmentMonths--
                        lastMonthAmount = lastMonthAmount + enteredAmount;
                    }

                    this.showInstallmentDetails = enteredAmount > 0 && this.numberOfInstallmentMonths > 0
                    this.showLastInstallmentDetails = lastMonthAmount > 0 && this.numberOfInstallmentMonths > 0

                    if (this.showInstallmentDetails) {
                        this.installmentAmount = `$${enteredAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`
                    }

                    if (this.showLastInstallmentDetails) {
                        this.lastMonthAmount = `$${lastMonthAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`
                    }

                    if (!this.showLastInstallmentDetails && !this.showInstallmentDetails) {
                        this.showInstallmentDetails = false
                    }
                },
                submitOffer() {
                    this.firstPayDateValidationError = ''
                    this.amountValidationError = ''

                    if (this.$wire.form.first_pay_date === '') {
                        this.firstPayDateValidationError = @js(__('validation.required', ['attribute' => __('First Pay Date')]))
                    }

                    if (this.$wire.form.amount === '') {
                        this.amountValidationError = @js(__('validation.required', ['attribute' => __('Amount')]))
                    }

                    if (isNaN(this.$wire.form.amount)) {
                        this.amountValidationError = @js(__('validation.numeric', ['attribute' => __('Amount')]))
                    }

                    if (this.$wire.form.first_pay_date !== '' && this.$wire.form.amount !== '' && ! isNaN(this.$wire.form.amount)) {
                        this.firstPayDate = new Date(this.$wire.form.first_pay_date).toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric'})
                        this.displayConfirmBox = true
                    }
                }
            }))
        </script>
    @endscript
</div>
