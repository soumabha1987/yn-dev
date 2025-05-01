@use('Illuminate\Support\Number')

<div>
    <main class="w-full pb-8">
        <div class="flex flex-col lg:flex-row items-start pb-6 gap-6 sm:gap-4">
            <div class="flex card pt-6 pb-6 items-start w-full lg:w-1/3 shadow-[0_4px_8px_rgba(0,0,0,0.1)]">
                <div class="flex flex-row lg:flex-col items-center lg:items-start w-full justify-between">
                    <div class="px-6">
                        <x-consumer.creditor-details :$creditorDetails>
                            <h2
                                class="uppercase text-primary text-lg lg:text-xl line-clamp-1 font-semibold cursor-pointer hover:underline hover:underline-offset-2 decoration-primary">
                                {{ $consumer->original_account_name }}
                            </h2>
                        </x-consumer.creditor-details>

                        <div class="flex flex-col text-black text-lg mt-1">
                            <span class="hidden lg:inline-flex font-bold">{{ __("Account Number") }}</span>
                            <span class="text-base text-slate-700">{{ $consumer->account_number }}</span>
                        </div>
                    </div>

                    <div class="px-6 lg:mt-8">
                        <p class="text-2xl text-primary lg:text-lg lg:text-black font-bold tracking-wide mt-1">
                            <span class="hidden lg:inline-flex">{{ __('Account Balance : ') }}</span>
                            <span>{{ Number::currency((float) $this->negotiationCurrentAmount($consumer)) }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div x-on:dialog:close.window="$wire.first_pay_date = ''" class="flex flex-col space-y-4 w-full lg:w-2/3">
                <div class="flex cursor-pointer card rounded-lg px-4 sm:px-6 py-6 sm:py-8 items-center min-h-[140px] sm:min-h-[180px] hover:outline outline-primary shadow-[0_8px_8px_rgba(0,0,0,0.1)]"
                    wire:click="createSettlementOfferWithToday">
                    <div class="flex flex-wrap w-full">
                        <div class="w-full md:w-2/3">
                            <h3 class="text-base md:text-lg font-semibold text-black">
                                {{ __('One time payment of :payOffDiscountBalance', ['payOffDiscountBalance' =>
                                Number::currency((float) $payOffDiscount)]) }}
                            </h3>
                            <p class="text-xs md:text-sm text-black">
                                {{ __('I\'m excited to pay this off and say bye-bye-bye forever!') }}
                            </p>

                            <div class="flex justify-between w-full">
                                @if ($payOffDiscountedAmount > 0)
                                <div class="block">
                                    <p class="text-primary whitespace-nowrap font-bold text-xl mt-3">
                                        {{ __('Save :amount', ['amount' => Number::currency($payOffDiscountedAmount)])
                                        }}
                                    </p>
                                </div>
                                @endif
                                <div class="sm:hidden flex flex-col content-center justify-end">
                                    <div class="block text-4xl text-center">😍</div>
                                    @if ($payOffDiscountedAmount > 0)
                                    <div
                                        class="bg-error px-3 py-1 text-white text-xs+ font-bold text-center rounded-full mt-1">
                                        {{ __(':off Off', ['off' => Number::percentage((float)
                                        $payOffDiscountPercentage)]) }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="w-full md:w-1/3">
                            <div class="hidden sm:flex flex-wrap content-center justify-end h-full">
                                <div class="min-w-[86px]">
                                    <div class="block text-6xl text-center">😍</div>
                                    @if ($payOffDiscountedAmount > 0)
                                    <div
                                        class="bg-error px-2.5 sm:px-3 py-1.5 sm:py-2 text-white text-xs sm:text-sm text-center sm:text-left rounded-full mt-2">
                                        {{ __(':off Off', ['off' => Number::percentage((float)
                                        $payOffDiscountPercentage)]) }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <x-consumer.dialog>
                    <x-consumer.dialog.open>
                        <div
                            class="flex cursor-pointer card rounded-lg px-4 sm:px-6 py-6 sm:py-8 items-center min-h-[140px] sm:min-h-[180px] hover:outline outline-primary shadow-[0_8px_8px_rgba(0,0,0,0.1)]">
                            <div class="flex flex-wrap w-full">
                                <div class="w-full md:w-2/3">
                                    <h3 class="text-base md:text-lg font-semibold text-black">
                                        {{ $installmentDetails['message'] }}
                                    </h3>

                                    <p class="text-xs md:text-sm text-black">
                                        {{ __('I am excited to pay this in :months payments', ['months' =>
                                        $installmentDetails['installments']]) }}
                                    </p>

                                    <div class="flex justify-between w-full">
                                        @if ($installmentDetails['discounted_amount'] > 0)
                                        <div class="block">
                                            <p class="text-primary font-bold text-xl mt-3">
                                                {{ __('Save :amount', ['amount' => Number::currency((float)
                                                $installmentDetails['discounted_amount'])]) }}
                                            </p>
                                        </div>
                                        @endif

                                        {{-- Mobile View --}}
                                        <div class="sm:hidden flex flex-col content-center justify-end">
                                            <div class="block text-4xl text-center">🤩</div>
                                            @if ($installmentDetails['discounted_amount'] > 0)
                                            <div
                                                class="bg-error px-3 py-1 text-white text-xs+ font-bold text-center rounded-full mt-1">
                                                {{ __(':off Off', ['off' => $installmentDetails['discount_percentage']])
                                                }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="w-full md:w-1/3">
                                    <div class="hidden sm:flex flex-wrap content-center justify-end h-full">
                                        <div class="min-w-[86px]">
                                            <div class="block text-6xl text-center">🤩</div>
                                            @if ($installmentDetails['discounted_amount'] > 0)
                                            <div
                                                class="bg-error px-2.5 sm:px-3 py-1.5 sm:py-2 text-white text-xs sm:text-sm text-center sm:text-left rounded-full mt-2">
                                                {{ __(':off Off', ['off' => $installmentDetails['discount_percentage']])
                                                }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-consumer.dialog.open>

                    <x-consumer.dialog.panel :heading="__('Choose First Payment Date')" class="h-[400px]">
                        <form x-ref="installmentOfferFrom" wire:submit="createInstallmentOffer" autocomplete="off">
                            <div class="mx-5">
                                <x-consumer.offers.first-pay-date :$maxFirstPayDate modelable="first_pay_date" />
                            </div>
                            <x-slot name="footer" class="flex flex-col sm:flex-row gap-2 sm:justify-between">
                                <x-consumer.dialog.close>
                                    <x-form.default-button type="button" class="w-full"
                                        x-on:click="$wire.first_pay_date = ''">
                                        {{ __('Close') }}
                                    </x-form.default-button>
                                </x-consumer.dialog.close>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    {{-- <x-consumer.dialog.close>
                                        <a wire:navigate
                                            href="{{ route('consumer.custom-offer.type', ['consumer' => $consumer, 'type' => 'installment']) }}"
                                            class="btn border w-full border-info/30 bg-info/10 font-medium text-info  hover:bg-info/20 focus:bg-info/20 active:bg-info/25">
                                            {{ __('Propose Different Date') }}
                                        </a>
                                    </x-consumer.dialog.close> --}}
                                    <x-consumer.form.button type="submit" variant="primary"
                                        class="border focus:border-primary-focus disabled:opacity-50 disabled:cursor-not-allowed"
                                        @click="$refs.installmentOfferFrom.requestSubmit()"
                                        x-bind:disabled="$wire.first_pay_date === ''">
                                        {{ __('Submit') }}
                                    </x-consumer.form.button>
                                </div>
                            </x-slot>
                        </form>
                    </x-consumer.dialog.panel>
                </x-consumer.dialog>

                <a wire:navigate href="{{ route('consumer.custom-offer', ['consumer' => $consumer]) }}">
                    <div
                        class="flex cursor-pointer card rounded-lg px-4 sm:px-6 py-6 sm:py-8 items-center min-h-[140px] sm:min-h-[180px] hover:outline outline-primary shadow-[0_8px_8px_rgba(0,0,0,0.1)]">
                        <div class="flex flex-wrap w-full">
                            <div class="w-full md:w-2/3">
                                <h3 class="text-base md:text-lg font-semibold text-black">
                                    {{ __('Create a custom offer') }}
                                </h3>

                                <div class="block">
                                    <p class="text-xs md:text-sm text-black">
                                        {{ __('Send an offer that I can afford to start knocking this account out') }}
                                    </p>
                                </div>
                            </div>

                            <div class="w-full md:w-1/3">
                                <div class="flex flex-wrap content-center justify-end h-full">
                                    <div class="min-w-[86px]">
                                        <div class="hidden sm:block text-6xl w-full text-center">😊</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>

                @if ($reasons->isNotEmpty())
                <div class="text-center">
                    <div class="flex items-center space-x-2">
                        <span>{{ __('Report that I\'m not paying?') }}</span>
                        <livewire:consumer.my-account.report-not-paying :consumer="$consumer" view="negotiate"
                            :key="str()->random(10)" />
                    </div>
                </div>
                @endif
            </div>
        </div>
    </main>
</div>