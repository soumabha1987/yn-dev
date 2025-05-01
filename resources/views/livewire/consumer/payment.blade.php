@use('Illuminate\Support\Number')
@use('App\Enums\MerchantName')
@use('App\Enums\MerchantType')
@use('App\Enums\NegotiationType')

@php
$amount = $consumer->consumerNegotiation->counter_offer_accepted
? (float) $consumer->consumerNegotiation->counter_negotiate_amount
: (float) $consumer->consumerNegotiation->negotiate_amount;

if ($consumer->consumerNegotiation->payment_plan_current_balance !== null) {
$amount = (float) $consumer->consumerNegotiation->payment_plan_current_balance;
}
@endphp

<div>
    <main x-data="payment" class="w-full pb-8">
        @php
        $negotiation = $consumer->consumerNegotiation;

        $isPif = $negotiation->negotiation_type === NegotiationType::PIF;

        $settlementDiscountPercent = $isPif
        ? $accountBalance['percentage']
        : $installments['discount_percentage'];

        $settlementAmount = $isPif
        ? $minimumPifDiscountedAmount
        : $amount;

        $settlementBalanceText = __(':payOffDiscount (:off Discount)', [
        'off' => Number::percentage((float) $settlementDiscountPercent),
        'payOffDiscount' => Number::currency((float) $settlementAmount)
        ]);
        @endphp

        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('consumer.negotiate', ['consumer' => $consumer->id]) }}"
                class="inline-flex items-center space-x-2 rounded-lg bg-gradient-to-r from-teal-500 to-blue-500 px-4 py-2 text-white shadow-lg transition hover:from-teal-600 hover:to-blue-600">
                <x-lucide-arrow-left class="w-5 h-5" />
                <span class="font-semibold">Back</span>
            </a>
        </div>

        <div class="flex card pt-6 pb-6 items-start w-full lg:w-2/3">
            <div class="flex flex-row lg:flex-col items-center lg:items-start w-full justify-between">
                <div class="px-6">
                    <x-consumer.creditor-details :$creditorDetails>
                        <h2
                            class="uppercase text-primary text-lg lg:text-xl line-clamp-1 font-semibold cursor-pointer hover:underline hover:underline-offset-2 decoration-primary">
                            {{ $consumer->original_account_name }}
                        </h2>
                    </x-consumer.creditor-details>

                    <div class="flex flex-col text-black text-base mt-1 font-semibold">
                        <span>{{ __('Account Balance : ') }} {{ Number::currency((float) $consumer->current_balance)
                            }}</span>
                    </div>

                    <div class="flex flex-col text-black text-base mt-1 font-semibold">
                        <span>{{ __('Settlement Balance : ') }} {{ $settlementBalanceText }}</span>
                    </div>

                    @if ($isPif)
                    <div class="flex flex-col text-black text-sm mt-4 font-semibold">
                        <x-consumer.generate-payment-link :consumer="$consumer">
                            <x-form.button type="button" variant="primary">
                                <span class="capitalize"
                                    x-tooltip.placement.bottom="@js('Your creditor will accept full settlement payments only. Send this link to someone to give you the gift of settling this account forever on your behalf!')">
                                    {{ __('Full Settlement Helping Hand Link') }}
                                </span>
                            </x-form.button>
                        </x-consumer.generate-payment-link>
                    </div>
                    @endif

                    @if ($negotiation->negotiation_type === NegotiationType::INSTALLMENT)
                    <div class="flex flex-col text-black text-base mt-1 font-semibold">
                        <span>
                            {{ __(':type Payment: :amount', [
                            'type' => Str::ucfirst($consumerNegotiation->installment_type->value),
                            'amount' => Number::currency((float) $consumerNegotiation->monthly_amount)
                            ]) }}
                        </span>
                    </div>

                    <div class="flex flex-col text-black text-base mt-1 font-semibold">
                        <span>
                            {{ __(
                            ':count Payments of :monthly' . ($consumerNegotiation->last_month_amount > 0 ? ' and one
                            last payment of :last' : ''),
                            [
                            'count' => $consumerNegotiation->no_of_installments,
                            'monthly' => Number::currency((float) $consumerNegotiation->monthly_amount),
                            'last' => Number::currency((float) $consumerNegotiation->last_month_amount)
                            ]
                            ) }}
                        </span>
                    </div>

                    @endif
                </div>
            </div>
        </div>


        <div class="grid grid-cols-1 sm:grid-cols-3 sm:gap-x-4">
            <div class="col-span-2">
                <div class="flex justify-center">
                    <div class="is-scrollbar-hidden col-span-8 overflow-x-auto rounded-lg bg-slate-200 text-slate-600">
                        <div class="flex p-1">
                            @if ($merchants->contains('merchant_type', MerchantType::CC))
                            <button type="button" role="tab" x-on:click="method = @js(MerchantType::CC->value)"
                                class="btn space-x-2 text-black shrink-0 px-3 py-1 text-xs+ font-semibold transition-all delay-100 hover:text-slate-700 focus:text-slate-700"
                                x-bind:class="{
                                        'bg-white shadow' : method === @js(MerchantType::CC->value),
                                        'hover:text-slate-800 focus:text-slate-800' : method !== @js(MerchantType::CC->value)
                                    }">
                                <x-lucide-credit-card class="size-5" />
                                <span>{{ MerchantType::CC->displayName() }}</span>
                            </button>
                            @endif

                            @if (
                            $merchants->pluck('merchant_name')->containsStrict(fn (MerchantName $merchantName) =>
                            $merchantName !== MerchantName::STRIPE)
                            && $merchants->contains('merchant_type', MerchantType::ACH)
                            )
                            <button type="button" role="tab" x-on:click="method = @js(MerchantType::ACH->value)"
                                class="btn space-x-2 text-black shrink-0 px-3 py-1 text-xs+ transition-all delay-100 font-semibold hover:text-slate-800 focus:text-slate-800"
                                x-bind:class="{
                                        'bg-white shadow' : method === @js(MerchantType::ACH->value),
                                        'hover:text-slate-800 focus:text-slate-800' : method !== @js(MerchantType::ACH->value)
                                    }">
                                <x-lucide-landmark class="size-5" />
                                <span>{{ MerchantType::ACH->displayName() }}</span>
                            </button>
                            @endif
                            @if (
                            ($consumer->offer_accepted && $consumer->payment_setup)
                            || ($consumer->offer_accepted && $consumer->consumerNegotiation->negotiation_type ===
                            NegotiationType::PIF)
                            )
                            <button type="button" role="tab" x-on:click="method = 'helping-hand-link'"
                                class="btn space-x-2 text-black shrink-0 px-3 py-1 text-xs+ transition-all delay-100 font-semibold hover:text-slate-800 focus:text-slate-800"
                                x-bind:class="{
                                        'bg-white shadow' : method === 'helping-hand-link',
                                        'hover:text-slate-800 focus:text-slate-800' : method !== 'helping-hand-link'
                                    }">
                                <x-lucide-heart-handshake class="size-5" />
                                <span>{{ __('Helping Hand Link') }}</span>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 sm:gap-x-4">
            @if ($merchants->contains('merchant_name', MerchantName::YOU_NEGOTIATE))
            <x-consumer.payment.you-negotiate :$merchants :$consumer :$termsAndCondition />
            @else
            <x-consumer.payment.form :$merchants :$consumer :$termsAndCondition :$savedCards />
            @endif

            @if (
            ($consumer->offer_accepted && $consumer->payment_setup)
            || ($consumer->offer_accepted && $consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF)
            )
            <template x-if="method === 'helping-hand-link'">
                <div class="card col-span-2 p-4">
                    <div class="border rounded h-full inset-0 flex items-center justify-center py-10">
                        <div class="flex flex-col gap-4 justify-center items-center text-center">
                            <img src="{{ asset('images/dfa.png') }}" class="inline w-60" alt="">

                            <h2 class="font-semibold tracking-wide text-black line-clamp-1 text-xl lg:text-2xl">
                                {{ __('Helping Hand') }}
                            </h2>

                            <p class="px-8 sm:px-12 lg:px-20 text-black">
                                {{ __('Easily create a secure payment link that allows others to contribute on your
                                behalf. Whether you need financial support from friends, family, or clients, this link
                                simplifies the process—just share it, and they can complete the payment hassle-free.')
                                }}
                            </p>

                            <div>
                                <x-consumer.generate-payment-link :consumer="$consumer">
                                    <x-form.button type="button" variant="primary">
                                        <span class="capitalize">{{ __('Generate Helping Hand Link') }}</span>
                                    </x-form.button>
                                </x-consumer.generate-payment-link>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            @endif

            <div class="card p-4 mt-8 sm:mt-0">
                <div class="my-3 flex h-8 items-center justify-between">
                    <h2 class="font-semibold tracking-wide text-black line-clamp-1 lg:text-xl">
                        {{ __('Payment Schedule') }}
                    </h2>
                </div>

                <div
                    class="col-span-12 rounded-lg bg-[conic-gradient(at_bottom_right,_var(--tw-gradient-stops))] from-teal-600 via-blue-200 to-neutral-100 p-3 sm:col-span-6 xl:col-span-5">
                    <div class="space-y-4 max-h-96 overflow-scroll is-scrollbar-hidden">
                        @if ($consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div>
                                    <p class="text-slate-700 font-semibold text-lg line-clamp-1">
                                        @if ($consumer->consumerNegotiation->counter_offer_accepted)
                                        {{ $consumer->consumerNegotiation->counter_first_pay_date->format('M d, Y') }}
                                        @else
                                        {{ $consumer->consumerNegotiation->first_pay_date->format('M d, Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <p class="font-semibold text-lg text-primary">
                                {{ Number::currency((float) $minimumPifDiscountedAmount) }}
                            </p>
                        </div>
                        @else
                        @foreach ($installmentDetails as $installmentDetail)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div>
                                    <p class="text-slate-700 text-xl font-semibold line-clamp-1">
                                        {{ $installmentDetail['schedule_date'] }}
                                    </p>
                                </div>
                            </div>
                            <p class="font-semibold text-xl text-primary">
                                {{ Number::currency((float) $installmentDetail['amount']) }}
                            </p>
                        </div>
                        @endforeach
                        @endif
                    </div>

                    <hr class="my-3 h-px border-dashed border-teal-700">

                    <div class="flex sm:flex-col lg:flex-row sm:items-start lg:items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div>
                                <p class="text-slate-700 text-xl font-semibold line-clamp-1">
                                    {{ __('Total Amount') }}
                                </p>
                            </div>
                        </div>
                        <p class="font-semibold text-xl text-primary">
                            @if ($consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF)
                            {{ Number::currency((float) $minimumPifDiscountedAmount) }}
                            @else
                            {{ Number::currency((float) $amount) }}
                            @endif
                        </p>
                    </div>
                </div>


                <div class="flex justify-between space-x-2 py-3 px-2">
                    <img src="{{ asset('images/secured.png') }}" alt="secure_payment">
                </div>

                <div class="flex justify-center mx-auto">
                    <img src="{{ asset('images/pci.png') }}" width="150px" alt="secure_payment">
                </div>
            </div>
        </div>
    </main>

    @script
    <script>
        Alpine.data('payment', () => ({
                method: @js(MerchantType::CC->value),
                init() {
                    if (this.$wire.form.method !== '') {
                        this.method = this.$wire.form.method
                    }

                    this.$watch('method', () => {
                        this.$dispatch('update-card-number')
                        this.$wire.form.method = this.method
                        this.$wire.form.card_number = ''
                        this.$wire.form.card_holder_name = ''
                        this.$wire.form.expiry = ''
                        this.$wire.form.cvv = ''
                        this.$wire.form.account_type = ''
                        this.$wire.form.account_number = ''
                        this.$wire.form.routing_number = ''
                        this.$wire.form.isTermsAccepted = false
                    })
                }
            }))
    </script>
    @endscript
</div>