@use('Illuminate\Support\Number')
@use('App\Enums\NegotiationType')

@props(['account' => null])

<x-consumer.dialog>
    <x-consumer.dialog.open>
        {{ $slot }}
    </x-consumer.dialog.open>

    <x-consumer.dialog.panel size="2xl">
        <x-slot name="heading">
            {{ __('Last Offer Details') }}
        </x-slot>

        @if ($account->lastOffer['account_profile_details'] ?? false)
            <div class="flex items-center justify-between p-3 border border-primary-400 rounded-lg bg-primary-light text-black flex-wrap">
                <div>
                    <h2 class="text-xl capitalize font-bold">{{ $account->lastOffer['account_profile_details']['creditor_name'] }}</h2>
                    <p class="text-xs+ sm:text-sm text-gray-700">{{ $account->lastOffer['account_profile_details']['account_number'] }}</p>
                </div>
                <div class="text-wrap">
                    <p class="text-xl font-bold"> {{ Number::currency((float) $account->lastOffer['account_profile_details']['current_balance']) }}</p>
                </div>
            </div>
        @endif

        @if ($account->lastOffer['offer_summary'] ?? false)
            <div class="border rounded-lg px-4 sm:px-5 mt-4 py-3">
                <div class="flex items-center justify-between text-black">
                    <h3 class="text-lg font-semibold">{{ __('My Last Offer') }}</h3>
                    @if ($account->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT)
                        <span class="badge whitespace-nowrap rounded-md bg-primary/10 text-primary">
                            {{ $account->consumerNegotiation->installment_type->displayName() }}
                        </span>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-4 mt-2">
                    <div>
                        <p class="text-black text-xs+ sm:text-sm+ font-semibold">
                            {{ $account->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT
                                ? __('Payment Plan')
                                : __('One-Time Settlement Offer')
                            }}
                        </p>
                        <p class="text-primary text-base sm:text-lg font-bold">
                            {{ $account->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT
                                ? Number::currency($account->consumerNegotiation->monthly_amount) .' / '.$account->consumerNegotiation->installment_type->value
                                : $account->lastOffer['offer_summary']['one_time_settlement']
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-black text-xs+ sm:text-sm+ font-semibold">
                            {{ $account->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT
                                ? __('First Payment Date')
                                : __('Settlement Payment Date')
                            }}
                        </p>
                        <p class="text-primary text-base sm:text-lg font-bold">
                            {{ $account->lastOffer['offer_summary']['first_payment_date'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <x-slot name="footer">
            <div class="space-x-2 mt-4">
                <x-consumer.dialog.close>
                    <x-form.default-button type="button">
                        {{ __('Close') }}
                    </x-form.default-button>
                </x-consumer.dialog.close>
                <x-consumer.dialog.close>
                    <a
                        wire:navigate
                        href="{{ route('consumer.custom-offer', ['consumer' => $account->id]) }}"
                        class="btn bg-success text-center font-medium text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                    >
                        {{ __('Change my offer') }}
                    </a>
                </x-consumer.dialog.close>
            </div>
        </x-slot>
    </x-consumer.dialog.panel>
</x-consumer.dialog>
