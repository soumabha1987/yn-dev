@use('Illuminate\Support\Number')
@use('App\Enums\ConsumerStatus')

<div>
    @if ($accounts->isEmpty())
        <div
            wire:loading.remove
            class="card h-20 w-full border border-slate-200 flex items-center justify-center mt-8"
        >
            <p class="text-sm+ font-bold">{{ __('No accounts found') }}</p>
        </div>

        <div
            wire:loading.grid
            class="container xl:px-16 2xl:px-32 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-3 lg:gap-6 mt-8"
        >
            @foreach (range(1, 3) as $index)
                <x-consumer.my-accounts.card-placeholder />
            @endforeach
        </div>
    @else
        <div class="container xl:px-16 2xl:px-32 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-3 lg:gap-6 mt-8">
            @foreach ($accounts as $account)
                <div wire:loading wire:target.except="downloadAgreement, startOver, dispute">
                    <x-consumer.my-accounts.card-placeholder />
                </div>

                <div
                    wire:loading.remove
                    wire:target.except="downloadAgreement, startOver, dispute"
                    class="card rounded-md border-[#2F3C4633] p-3"
                >
                    <div class="flex justify-between items-center mt-3">
                        <div class="inline-space ml-3 flex grow flex-wrap items-start">
                            <x-consumer.my-accounts.card-view.actions
                                :$account
                                type="tab"
                            />
                        </div>
                        @if (! $account->expiry_date)
                            <div class="p-2 text-right bg-indigo-60">
                                <x-consumer.menu>
                                    <x-consumer.menu.button>
                                        <x-heroicon-m-ellipsis-vertical class="size-5 text-slate-500" />
                                    </x-consumer.menu.button>

                                    <x-consumer.menu.items
                                        position="bottom-end"
                                        class="!w-56"
                                    >
                                        <x-consumer.creditor-details :creditorDetails="$account->creditorDetails">
                                            <x-consumer.menu.close>
                                                <x-consumer.menu.item>
                                                    <span>{{ __('Account Contact Details') }}</span>
                                                </x-consumer.menu.item>
                                            </x-consumer.menu.close>
                                        </x-consumer.creditor-details>

                                        @if ($account->accountConditions === 'payment_accepted_and_plan_in_scheduled')
                                            <x-consumer.menu.item
                                                wire:click="downloadAgreement({{ $account->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="downloadAgreement({{ $account->id }})"
                                            >
                                                <div @close-menu.window="menuOpen = false">
                                                    <x-lucide-loader-2 wire:loading wire:target="downloadAgreement({{ $account->id }})" class="size-5 animate-spin" />
                                                    <span>{{ __('Download agreement') }}</span>
                                                </div>
                                            </x-consumer.menu.item>
                                            <a
                                                wire:navigate
                                                href="{{ route('consumer.payment', ['consumer' => $account->id]) }}"
                                            >
                                                <x-consumer.menu.item>
                                                        {{ __('Change payment method') }}
                                                </x-consumer.menu.item>
                                            </a>
                                        @endif

                                        @if ($account->accountConditions === 'joined')
                                            <livewire:consumer.my-account.report-not-paying
                                                :consumer="$account"
                                                :key="str()->random(10)"
                                                view="my-account"
                                            />
                                        @endif

                                        @if (in_array($account->accountConditions, ['payment_accepted_and_plan_in_scheduled', 'hold']))
                                            <livewire:consumer.my-account.hold
                                                :consumer="$account"
                                                :key="str()->random(10)"
                                                page="my-account"
                                            />
                                        @endif
                                        @if ($account->accountConditions === 'hold')
                                            <livewire:consumer.my-account.restart-plan
                                                :consumer="$account"
                                                page="my-account"
                                                :key="str()->random(10)"
                                            />
                                        @endif

                                        @if (in_array($account->accountConditions, ['hold', 'settled', 'deactivated']))
                                            <x-consumer.menu.item
                                                wire:click="downloadAgreement({{ $account->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="downloadAgreement({{ $account->id }})"
                                            >
                                                <div @close-menu.window="menuOpen = false">
                                                    <x-lucide-loader-2 wire:loading wire:target="downloadAgreement({{ $account->id }})" class="size-5 animate-spin" />
                                                    <span>{{ __('Download agreement') }}</span>
                                                </div>
                                            </x-consumer.menu.item>
                                        @endif

                                        @if (in_array($account->accountConditions, ['approved_but_payment_setup_is_pending', 'approved_settlement_but_payment_setup_is_pending']) && $account->consumerNegotiation)
                                            <livewire:consumer.my-account.change-first-pay-date
                                                :consumer="$account"
                                                :key="str()->random(10)"
                                            />
                                        @endif

                                        @if (
                                            in_array($account->accountConditions, [
                                                'approved_settlement_but_payment_setup_is_pending',
                                                'payment_accepted_and_plan_in_scheduled',
                                                'renegotiate',
                                                'creditor_send_an_offer',
                                                'joined',
                                            ])
                                        )
                                            <x-consumer.generate-payment-link :consumer="$account">
                                                <x-consumer.menu.close>
                                                    <x-consumer.menu.item>
                                                        <span class="capitalize">{{ __('Helping Hand Link') }}</span>
                                                    </x-consumer.menu.item>
                                                </x-consumer.menu.close>
                                            </x-consumer.generate-payment-link>
                                        @endif
                                    </x-consumer.menu.items>
                                </x-consumer.menu>
                            </div>
                        @endif
                    </div>

                    @if ($account->reason_id)
                        <div class="px-4 sm:px-5 text-error font-semibold text-xs pt-1">
                            <span x-tooltip.placement.bottom="@js($account->reason->label)">{{ str($account->reason->label)->limit(20) }}</span>
                        </div>
                    @endif

                    @if (in_array($account->accountConditions, ['disputed', 'deactivated']))
                        <div class="px-4 sm:px-5 text-error font-semibold text-xs pt-1">
                            {{ __('On '). $account->disputed_at?->format('M d, Y') }}
                        </div>
                    @endif

                    @if (in_array($account->accountConditions, ['approved_but_payment_setup_is_pending', 'approved_settlement_but_payment_setup_is_pending']) && $account->consumerNegotiation)
                        @php
                            $firstPayDate = $account->consumerNegotiation->counter_offer_accepted
                                ? $account->consumerNegotiation->counter_first_pay_date
                                : $account->consumerNegotiation->first_pay_date
                        @endphp
                        <div class="px-4 sm:px-5 text-error font-semibold text-xs pt-1">
                            {{ __('Offer expires on :firstPayDate', ['firstPayDate' => $firstPayDate->format('M d, Y')]) }}
                        </div>
                    @endif

                    @if ($account->accountConditions === 'hold')
                        <div class="px-4 sm:px-5 text-error font-semibold text-xs pt-1">
                            {{ __('Restart on :date', ['date' => $account->restart_date->format('M d, Y')]) }}
                        </div>
                    @endif

                    <div class="flex grow flex-col px-4 sm:px-5">
                        <div class="w-full mt-3">
                            <p class="text-xl capitalize font-medium text-slate-700">
                                <span x-tooltip.placement.bottom="@js($account->original_account_name)">
                                    {{ str($account->original_account_name)->limit(20) }}
                                </span>
                            </p>
                            <span
                                class="text-sm+ text-black hover:cursor-pointer hover:underline"
                                x-tooltip.placement.bottom="@js(__('Original Account Number'))"
                            >
                                {{ $account->account_number }}
                            </span>
                        </div>

                        <div class="w-full mt-5 grid grid-cols-2 gap-1">
                            <div>
                                <p class="text-sm+">
                                    {{ $account->status === ConsumerStatus::SETTLED ? __('Payoff Balance') : __('Current Balance') }}
                                </p>
                                <p class="text-xl font-medium text-slate-700">
                                    {{ Number::currency((float) $account->negotiateCurrentAmount) }}
                                </p>
                            </div>
                            @if ($account->status === ConsumerStatus::SETTLED)
                                <div>
                                    <p class="text-sm+">
                                        {{ __('Current Balance') }}
                                    </p>
                                    <p class="text-xl font-medium text-slate-700">
                                        {{ Number::currency(0) }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        <div class="mt-5">
                            <p class="text-sm+">{{ __('Account Placement') }}</p>
                            <x-consumer.creditor-details :creditorDetails="$account->creditorDetails">
                                <h3 class="cursor-pointer text-lg font-medium text-primary hover:underline capitalize">
                                    {{ $account->company->company_name }}
                                </h3>
                            </x-consumer.creditor-details>
                            <span
                                class="text-sm+ text-black mt-1.5 hover:cursor-pointer hover:underline"
                                x-tooltip.placement.bottom="@js(__('Member Account Number'))"
                            >
                                {{ $account->member_account_number }}
                            </span>
                        </div>

                        @if (! in_array($account->accountConditions, ['disputed', 'deactivated', 'not_paying', 'settled']) && ! $account->offer_accepted)
                            @if ($account->expiry_date)
                                <span class="text-error font-semibold mt-2">
                                    {{ __('Deadline Date: :date', ['date' => $account->expiry_date->format('M d, Y')]) }}
                                </span>
                            @else
                                <span class="text-slate-500 font-semibold mt-2">
                                    {{ __('No deadline date') }}
                                </span>
                            @endif
                        @endif


                        <hr class="border-t-2 border-dashed border-gray-300 my-3">
                    </div>

                    <x-consumer.my-accounts.card-view.actions
                        :$account
                        type="button"
                    />
                </div>
            @endforeach
        </div>
    @endif
</div>
