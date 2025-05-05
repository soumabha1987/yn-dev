@use('Illuminate\Support\Number')
@use('App\Enums\MerchantName')
@use('App\Enums\MerchantType')

<div x-data="externalPayment">
    <div class="card py-24 md:py-8 bg-center bg-cover sm:bg-contain lg:bg-cover bg-no-repeat bg-[url('/images/external-payment/thanks-mobile.png')] md:bg-[url('/images/external-payment/thanks.png')]">
        <div
            x-on:scroll-into-amount.window="$el.scrollIntoView()"
            class="flex flex-col space-y-3 py-8 sm:py-2 items-center justify-center text-center"
        >
            <span class="text-2xl sm:text-3xl tracking-widest font-semibold">{{ __('Helping Hand!') }}</span>
            @if ($amountIsEditable)
                <p class="text-base sm:text-lg px-2 font-medium sm:max-w-md lg:max-w-2xl">
                    {{ __('You can make a tax-deductible payment to help :consumerName pay off their payment plan faster and become debt-free!', ['consumerName' => $consumer->first_name . ' ' . $consumer->last_name]) }}
                </p>
                <span class="text-xl sm:text-2xl text-primary font-bold">
                    <label class="flex items-center">
                        <span class="text-slate-500 pr-2">{{ __('Donation Amount: ') }}</span>
                        <input
                            type="number"
                            x-on:input="setAmount"
                            step="0.01"
                            form="make-payment-form"
                            class="border border-gray-300 px-2 py-1 rounded focus:outline-none w-40"
                            placeholder="{{ __('0.00') }}"
                            required
                            autocomplete="off"
                        >
                    </label>
                </span>
                @error('amount')
                    <span class="text-error">
                        {{ $message }}
                    </span>
                @enderror
            @else
                <p class="text-base sm:text-lg px-2 font-medium sm:max-w-md lg:max-w-2xl">
                    {{ __('Make a tax-deductible settlement payment to gift :consumerName financial freedom and a fresh start!', ['consumerName' => $consumer->first_name . ' ' . $consumer->last_name]) }}
                </p>
                <span class="text-center text-xl sm:text-2xl text-primary font-bold">
                    {{ Number::currency($totalPayableAmount) }}
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 sm:gap-x-4 mt-8">
        <div class="card h-fit px-6 py-8 sm:mt-0">
            <div>
                <h2 class="uppercase text-base lg:text-lg font-bold tracking-wide text-primary line-clamp-1">
                    {{ $consumer->subclient?->subclient_name ?? $consumer->company->company_name }}
                </h2>
                <p class="text-base lg:text-lg font-semibold tracking-wide mt-1 text-black">
                    <span>{{ $consumer->account_number }}</span>
                </p>
            </div>
            <div class="mt-8">
                <h2 class="font-bold text-base lg:text-lg tracking-wide text-primary line-clamp-1">
                    {{ $consumer->first_name . ' ' . $consumer->last_name }}
                </h2>
                <p class="text-base lg:text-lg font-semibold tracking-wide mt-1 text-black">
                    <span>{{ $amountIsEditable ? __('Current Balance : ') : __('Original Balance : ') }}</span>
                    <span>{{ $amountIsEditable ? Number::currency($totalPayableAmount) : Number::currency((float) $this->negotiationCurrentAmount($consumer)) }}</span>
                </p>
            </div>

            <div class="mt-8">
                <h2 class="font-bold text-base lg:text-lg tracking-wide text-primary line-clamp-1">
                    {{ $amountIsEditable ? __('Monthly Payment') : __('Settlement/Payoff') }}
                </h2>
                <p class="text-base lg:text-lg font-semibold tracking-wide mt-1 text-black">
                    {{ Number::currency($amountIsEditable ? $monthlyPayableAmount : $totalPayableAmount) }}
                </p>
            </div>
        </div>

        <div class="col-span-2 w-full mt-8 sm:mt-0">
            @if (
                $merchants->pluck('merchant_name')->containsStrict(fn (MerchantName $merchantName) => $merchantName !== MerchantName::STRIPE)
                && $merchants->count() === 2
            )
                <div class="flex justify-center">
                    <div class="is-scrollbar-hidden col-span-8 overflow-x-auto rounded-lg bg-slate-200 text-slate-600">
                        <div class="flex p-1">
                            <button
                                type="button"
                                role="tab"
                                x-on:click="method = @js(MerchantType::CC->value)"
                                class="btn space-x-2 text-black shrink-0 px-3 py-1 text-xs+ font-semibold transition-all delay-100 hover:text-slate-700 focus:text-slate-700"
                                x-bind:class="{
                                    'bg-white shadow' : method === @js(MerchantType::CC->value),
                                    'hover:text-slate-800 focus:text-slate-800' : method !== @js(MerchantType::CC->value)
                                }"
                            >
                                <x-lucide-credit-card class="size-5" />
                                <span>{{ MerchantType::CC->displayName() }}</span>
                            </button>
                            <button
                                type="button"
                                role="tab"
                                x-on:click="method = @js(MerchantType::ACH->value)"
                                class="btn space-x-2 text-black shrink-0 px-3 py-1 text-xs+ transition-all delay-100 font-semibold hover:text-slate-800 focus:text-slate-800"
                                x-bind:class="{
                                    'bg-white shadow' : method === @js(MerchantType::ACH->value),
                                    'hover:text-slate-800 focus:text-slate-800' : method !== @js(MerchantType::ACH->value)
                                }"
                            >
                                <x-lucide-landmark class="size-5" />
                                <span>{{ MerchantType::ACH->displayName() }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            @elseif ($merchants->count() === 1)
                @if ($merchants->first()->merchant_type === MerchantType::ACH)
                    <span x-init="method = @js(MerchantType::ACH->value)"></span>
                @endif

                @if ($merchants->first()->merchant_type === MerchantType::CC)
                    <span x-init="method = @js(MerchantType::CC->value)"></span>
                @endif
            @endif
            @if ($merchants->contains('merchant_name', MerchantName::YOU_NEGOTIATE))
                <x-consumer.payment.you-negotiate
                    :$merchants
                    :$consumer
                    :$isDisplayName
                />
            @else
                <x-consumer.payment.form
                    :$merchants
                    :$isDisplayName
                    :$savedCards
                />
            @endif
        </div>
    </div>

    <x-consumer.dialog x-model="paymentIsSuccessful">
        <x-consumer.dialog.panel
            :blur="true"
            :blankPanel="true"
        >
            <div class="bg-gray-100 bg-[url('/images/external-payment/success-payment.png')] bg-cover bg-bottom rounded-lg shadow-lg text-center max-w-lg">
                <div class="relative bg-primary rounded-full text-white rounded-t-lg p-2">
                    <div class="block text-white p-3 lg:p-6">
                        <div class="text-4xl lg:text-8xl mb-4">😍</div>
                    </div>
                </div>

                <div class="py-4 lg:py-6 px-6">
                    <p class="text-lg lg:text-2xl text-primary font-semibold">
                        {{ __('Thank you for making a payment to help :first_name.', ['first_name' => $consumer->first_name]) }}
                    </p>
                    <p class="text-sm text-gray-600">{{ __('You\'re a real hero!') }}</p>
                    <p class="mt-4 text-black font-semibold">{{ __('Donation Amount:') }}</p>
                    <p class="text-lg lg:text-2xl font-bold text-primary">
                        @if ($amountIsEditable)
                            {{ Number::currency((float) $amount) }}
                        @else
                            {{ Number::currency($totalPayableAmount) }}
                        @endif
                    </p>
                </div>

                <div
                    class="flex flex-col sm:flex-row gap-4 justify-between py-4 lg:py-6 px-6"
                    x-on:dont-close-dialog.window="$wire.paymentIsSuccessful = true"
                >
                    <button
                        class="bg-primary text-white font-bold py-3 px-6 rounded-full hover:bg-primary-focus"
                        wire:click="downloadReceipt"
                        tabindex="-1"
                    >
                        {{ __('Download Tax Receipt') }}
                    </button>
                    <a
                        href="https://younegotiate.com"
                        class="bg-primary text-white font-bold py-3 px-6 rounded-full hover:bg-primary-focus"
                        tabindex="-1"
                    >
                        {{ __('Homepage') }}
                    </a>
                </div>
            </div>
        </x-consumer.dialog.panel>
    </x-consumer.dialog>

    @script
        <script>
            Alpine.data('externalPayment', () => ({
                method: @js(MerchantType::CC->value),
                paymentIsSuccessful: false,
                init() {
                    this.$wire.form.method = this.method
                    this.paymentIsSuccessful = this.$wire.paymentIsSuccessful

                    this.$watch('paymentIsSuccessful', () => {
                        if (this.$wire.paymentIsSuccessful) {
                            this.paymentIsSuccessful = this.$wire.paymentIsSuccessful
                        }
                    })

                    this.$wire.$watch('paymentIsSuccessful', () => {
                        this.paymentIsSuccessful = this.$wire.paymentIsSuccessful
                    })

                    this.$watch('method', () => {
                        this.$wire.form.method = this.method
                        this.$wire.form.first_name = ''
                        this.$wire.form.last_name = ''
                        this.$wire.form.address = ''
                        this.$wire.form.city = ''
                        this.$wire.form.state = ''
                        this.$wire.form.zip = ''
                        this.$wire.form.card_number = ''
                        this.$wire.form.card_holder_name = ''
                        this.$wire.form.expiry = ''
                        this.$wire.form.account_type = ''
                        this.$wire.form.account_number = ''
                        this.$wire.form.routing_number = ''
                        this.$wire.form.isTermsAccepted = false
                    })
                },
                setAmount () {
                    let amount = this.$el.value
                    if (amount === '') return

                    if (amount > @js($totalPayableAmount)) {
                        this.$el.value = parseFloat(@js($totalPayableAmount)).toFixed(2);
                        this.$wire.amount = this.$el.value
                        return
                    }

                    this.$wire.amount = amount
                }
            }))
        </script>
    @endscript
</div>
