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
            wire:loading.flex
            class="mt-8"
        >
            <div class="min-w-full overflow-x-auto is-scrollbar-hidden">
                <table class="w-full text-left text-md">
                    <thead>
                        <tr class="border-y border-slate-200">
                            <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs">
                                {{ __('Account Name') }}
                            </th>
                            <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs">
                                {{ __('Account Number') }}
                            </th>
                            <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs">
                                {{ __("Currently Placed") }}
                            </th>
                            <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs">
                                {{ __('Current Balance') }}
                            </th>
                            <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs lg:w-1/4">
                                {{ __('Status') }}
                            </th>
                            <th class="text-center whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs lg:w-1/4">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                        <tr
                            wire:loading.class="!table-row"
                            class="h-[0.2rem] bg-gradient-to-r from-primary-200 to-primary loader-line"
                        >
                            <td colspan="20"></td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (range(1, 2) as $index)
                            <tr wire:loading.class="!table-row">
                                <td class="whitespace-nowrap border border-t-0 border-slate-200 p-3 lg:p-5">
                                    <div class="skeleton animate-wave px-4 py-3 sm:px-5 rounded bg-slate-150"></div>
                                </td>
                                <td class="whitespace-nowrap border border-t-0 border-slate-200 p-3 lg:p-5">
                                    <div class="skeleton animate-wave px-4 py-3 sm:px-5 rounded bg-slate-150"></div>
                                </td>
                                <td class="whitespace-nowrap border border-l-0 border-t-0 border-slate-200 p-3 lg:p-5">
                                    <div class="skeleton animate-wave px-4 py-3 sm:px-5 rounded bg-slate-150"></div>
                                </td>
                                <td class="whitespace-nowrap border border-l-0 border-t-0 border-slate-200 p-3 lg:p-5">
                                    <div class="skeleton animate-wave px-4 py-3 sm:px-5 rounded bg-slate-150"></div>
                                </td>
                                <td class="whitespace-nowrap border border-l-0 border-t-0 border-slate-200 p-3 lg:p-5 lg:w-1/4">
                                    <div class="skeleton animate-wave px-4 py-3 sm:px-5 rounded bg-slate-150"></div>
                                </td>
                                <td class="whitespace-nowrap border border-l-0 border-t-0 border-slate-200 p-3 lg:p-5 lg:w-1/4">
                                    <div class="skeleton animate-wave px-4 py-3 sm:px-5 rounded bg-slate-150"></div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="mt-8">
            <div class="min-w-full overflow-x-auto is-scrollbar-hidden">
                <table class="w-full text-left text-base">
                    <thead>
                        <tr class="border-y border-slate-200">
                            <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs+">
                                {{ __('Account Name') }}
                            </th>
                            <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs+">
                                {{ __('Account Number') }}
                            </th>
                            <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs+">
                                {{ __("Currently Placed") }}
                            </th>
                            <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs+">
                                {{ __('Current Balance') }}
                            </th>
                            <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs+ lg:w-1/4">
                                {{ __('Status') }}
                            </th>
                            <th class="text-center whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-3 py-2 font-semibold uppercase text-slate-800 text-xs+ lg:w-1/4">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                        <tr
                            wire:loading
                            wire:loading.class="!table-row"
                            class="h-[0.2rem] bg-gradient-to-r from-primary-200 to-primary loader-line"
                        >
                            <td colspan="20"></td>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $states = ConsumerStatus::consumerStates();
                        @endphp
                        @foreach ($accounts as $account)
                            @php
                                $stateName = array_flip(ConsumerStatus::sortingPriorityByStates())[$account->state_priority];
                                $stateDetails = $states[$stateName];
                            @endphp
                            <tr
                                wire:loading
                                wire:loading.class="!table-row"
                                wire:target.except="downloadAgreement, startOver, dispute"
                            >
                                <td class="whitespace-nowrap border border-t-0 border-slate-200 p-3 lg:p-5">
                                    <div class="skeleton animate-wave px-3 py-2 rounded bg-slate-150"></div>
                                </td>
                                <td class="whitespace-nowrap border border-t-0 border-slate-200 p-3 lg:p-5">
                                    <div class="skeleton animate-wave px-3 py-2 rounded bg-slate-150"></div>
                                </td>
                                <td class="whitespace-nowrap border border-l-0 border-t-0 border-slate-200 p-3 lg:p-5">
                                    <div class="skeleton animate-wave px-3 py-2 rounded bg-slate-150"></div>
                                </td>
                                <td class="whitespace-nowrap border border-l-0 border-t-0 border-slate-200 p-3 lg:p-5">
                                    <div class="skeleton animate-wave px-3 py-2 rounded bg-slate-150"></div>
                                </td>
                                <td class="whitespace-nowrap border border-l-0 border-t-0 border-slate-200 p-3 lg:p-5 lg:w-1/4">
                                    <div class="skeleton animate-wave px-3 py-2 rounded bg-slate-150"></div>
                                </td>
                                <td class="whitespace-nowrap border border-l-0 border-t-0 border-slate-200 p-3 lg:p-5 lg:w-1/4">
                                    <div class="skeleton animate-wave px-3 py-2 rounded bg-slate-150"></div>
                                </td>
                            </tr>
                            <tr
                                wire:loading.remove
                                wire:target.except="downloadAgreement, startOver, dispute"
                                class="border-y border-slate-200 text-sm+"
                            >
                                <td class="whitespace-nowrap capitalize border-x px-3 py-2">
                                    <span x-tooltip.placement.bottom="@js($account->original_account_name)">
                                        {{ str($account->original_account_name)->limit(15) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap border-x px-3 py-2">{{ $account->account_number }}</td>
                                <td class="cursor-pointer text-primary capitalize whitespace-nowrap border-x p-2 hover:underline hover:underline-offset-2">
                                    <x-consumer.creditor-details :creditorDetails="$account->creditorDetails">
                                        <span
                                            x-tooltip.placement.bottom="@js($account->subclient->subclient_name ?? $account->company->company_name)"
                                        >
                                            {{ str($account->subclient->subclient_name ?? $account->company->company_name)->words(3) }}
                                        </span>
                                    </x-consumer.creditor-details>
                                </td>
                                <td class="border-x px-3 py-2">
                                    <div class="flex items-center gap-1">
                                        <span>{{ Number::currency($account->status !== ConsumerStatus::SETTLED ? (float) $account->negotiateCurrentAmount : 0) }}</span>
                                        @if ($account->status === ConsumerStatus::SETTLED)
                                            <span x-tooltip.placement.bottom="@js(__('Payoff Balance: :amount', ['amount' => Number::currency((float) $account->negotiateCurrentAmount)]))">
                                                <x-lucide-circle-help class="size-4 hover:text-gray-500" />
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap border-x px-3 py-2">
                                    <span class="tag cursor-auto px-4 text-sm+ rounded-full {{ $stateDetails['grid_tag_class'] }}">
                                        {{ $stateName === 'active_negotiation' ? ($account->counter_offer ? 'Counter Offer Received' : 'Pending Creditor Response') : $stateDetails['card_title'] }}
                                    </span>
                                    @if ($account->reason_id)
                                        <div class="text-error font-semibold text-xs pt-1">
                                            <span x-tooltip.placement.bottom="@js($account->reason->label)">
                                                {{ str($account->reason->label)->limit(20) }}
                                            </span>
                                        </div>
                                    @endif
                                    @if (in_array($account->accountConditions, ['disputed', 'deactivated']))
                                        <div class="text-error font-semibold text-xs pt-1">
                                            {{ $account->disputed_at?->format('M d, Y') }}
                                        </div>
                                    @endif
                                    @if (in_array($account->accountConditions, ['approved_but_payment_setup_is_pending', 'approved_settlement_but_payment_setup_is_pending']) && $account->consumerNegotiation)
                                        @php
                                            $firstPayDate = $account->consumerNegotiation->counter_offer_accepted
                                                ? $account->consumerNegotiation->counter_first_pay_date
                                                : $account->consumerNegotiation->first_pay_date
                                        @endphp
                                        <div class="text-error font-semibold text-xs pt-1">
                                            {{ __('Offer expires on :firstPayDate',
                                                [
                                                    'firstPayDate' => $firstPayDate->format('M d, Y'),
                                                ])
                                            }}
                                        </div>
                                    @endif
                                    @if ($account->accountConditions === 'hold')
                                        <div class="text-error font-semibold text-xs pt-1">
                                            {{ __('Restart on :date', ['date' => $account->restart_date->format('M d, Y')]) }}
                                        </div>
                                    @endif
                                    @if (! in_array($account->accountConditions, ['disputed', 'deactivated', 'not_paying', 'settled']) && ! $account->offer_accepted)
                                        <div class="font-semibold text-xs pt-1 {{ $account->expiry_date ? 'text-error' : 'text-slate-500' }}">
                                            @if ($account->expiry_date)
                                                {{ __('Deadline Date: :date', ['date' => $account->expiry_date->format('M d, Y')]) }}
                                            @else
                                                {{ __('No deadline date') }}
                                            @endif
                                        </div>
                                    @endif

                                </td>
                                <td class="p-2 border-x px-4 py-3">
                                    <div class="md:flex items-center gap-x-3 justify-end">
                                        @if ($account->accountConditions === 'joined')
                                            <div class="grid w-full gap-2">
                                                <a
                                                    wire:navigate
                                                    href="{{ route('consumer.negotiate', ['consumer' => $account->id]) }}"
                                                    class="btn text-sm text-nowrap space-x-2 bg-success p-2 font-medium text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                                                >
                                                    {{ __('View My Offers!') }}
                                                </a>
                                            </div>
                                        @elseif ($account->accountConditions === 'deactivated' && $account->transactions_count > 0)
                                            <div class="grid w-full gap-2">
                                                <a
                                                    wire:navigate
                                                    href="{{ route('consumer.payment_history', ['consumer' => $account->id]) }}"
                                                    class="btn w-full text-nowrap text-sm space-x-2 bg-primary p-2 font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                                                >
                                                    {{ __('View Pay History') }}
                                                </a>
                                            </div>
                                        @elseif (in_array($account->accountConditions, ['approved_but_payment_setup_is_pending', 'approved_settlement_but_payment_setup_is_pending']))
                                            <div class="grid w-full gap-2">
                                                <a
                                                    wire:navigate
                                                    href="{{ route('consumer.payment', ['consumer' => $account->id]) }}"
                                                    class="btn text-sm text-nowrap space-x-2 bg-error p-2 font-medium text-white hover:bg-error-focus focus:bg-error-focus active:bg-error-focus/90"
                                                >
                                                    {{ __('Add My Payment') }}
                                                </a>
                                            </div>
                                        @elseif ($account->accountConditions === 'creditor_send_an_offer')
                                            <div class="grid w-full gap-2">
                                                <livewire:consumer.my-account.view-offer
                                                    :consumer="$account"
                                                    :key="str()->random(10)"
                                                    view="grid"
                                                />
                                            </div>
                                        @elseif ($account->accountConditions === 'pending_creditor_response')
                                            <div class="grid w-full gap-2">
                                                <x-consumer.my-accounts.last-offer :$account>
                                                    <button
                                                        type="button"
                                                        class="btn text-nowrap text-sm w-full space-x-2 p-2 bg-info font-medium text-white hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                                                    >
                                                        {{ __('Open Last Offer') }}
                                                    </button>
                                                </x-consumer.my-accounts.last-offer>
                                            </div>
                                        @elseif (in_array($account->accountConditions, ['payment_accepted_and_plan_in_scheduled', 'hold']))
                                            <div class="grid w-full gap-2">
                                                <a
                                                    wire:navigate
                                                    href="{{ route('consumer.schedule_plan', ['consumer' => $account->id]) }}"
                                                    class="btn text-nowrap text-sm space-x-2 bg-info p-2 font-medium text-white hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                                                >
                                                    {{ __('Open Payment Plan') }}
                                                </a>
                                            </div>
                                        @elseif (in_array($account->accountConditions, [
                                                'renegotiate',
                                                'declined',
                                            ])
                                        && $account->negotiation_count <= 3)
                                            <div class="grid w-full gap-2">
                                                <button
                                                    wire:click="startOver({{ $account->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="startOver({{ $account->id }})"
                                                    class="btn text-nowrap text-sm disabled:opacity-50 space-x-2 bg-accent p-2 font-medium text-white hover:bg-accent-focus focus:bg-accent-focus active:bg-accent-focus/90"
                                                >
                                                    <x-lucide-loader-2
                                                        wire:loading
                                                        wire:target="startOver({{ $account->id }})"
                                                        class="animate-spin size-5"
                                                    />
                                                    <span class="!ml-0">{{ __('Start over') }}</span>
                                                </button>
                                            </div>
                                        @elseif ($account->accountConditions === 'settled')
                                            <div class="grid w-full gap-2">
                                                <a
                                                    wire:navigate
                                                    href="{{ route('consumer.complete_payment', ['consumer' => $account->id]) }}"
                                                    class="btn text-nowrap text-sm space-x-2 bg-info p-2 font-medium text-white hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                                                >
                                                    {{ __('View My Payments') }}
                                                </a>
                                            </div>
                                        @elseif ($account->accountConditions === 'not_paying')
                                            <div class="grid w-full">
                                                <button
                                                    type="button"
                                                    wire:click="restart({{ $account->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restart({{ $account->id }})"
                                                    class="btn text-nowrap w-full text-sm disabled:opacity-50 space-x-2 bg-secondary-orange p-2 font-medium text-white hover:bg-secondary-orange-dark focus:bg-secondary-orange-dark active:bg-secondary-orange-dark"
                                                >
                                                    <span class="!ml-0">{{ __('Restart') }}</span>
                                                </button>
                                            </div>
                                        @endif
                                        @if (! $account->expiry_date)
                                            <x-consumer.menu>
                                                <x-consumer.menu.button>
                                                    <x-lucide-ellipsis
                                                        x-tooltip.placement.bottom="'{{ __('More Actions...') }}'"
                                                        class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25"
                                                    />
                                                </x-consumer.menu.button>

                                                <x-consumer.menu.items
                                                    position="left"
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
                                                        <x-consumer.menu.item>
                                                            <a
                                                                wire:navigate
                                                                href="{{ route('consumer.payment', ['consumer' => $account->id]) }}"
                                                            >
                                                                {{ __('Change payment method') }}
                                                            </a>
                                                        </x-consumer.menu.item>
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

                                                    @if ($account->accountConditions === 'renegotiate')
                                                        <x-consumer.menu.item>
                                                            <a
                                                                wire:navigate
                                                                href="{{ route('consumer.negotiate', ['consumer' => $account->id]) }}"
                                                            >
                                                            {{ __('Re-negotiate') }}
                                                            </a>
                                                        </x-consumer.menu.item>
                                                    @endif

                                                    @if (
                                                        in_array($account->accountConditions, ['approved_but_payment_setup_is_pending', 'approved_settlement_but_payment_setup_is_pending'])
                                                        && $account->consumerNegotiation
                                                    )
                                                        <livewire:consumer.my-account.change-first-pay-date
                                                            :consumer="$account"
                                                            :key="str()->random(10)"
                                                        />
                                                    @endif

                                                    @if (in_array($account->accountConditions, ['settled', 'hold', 'deactivated']))
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
                                                </x-consumer.menu.items>
                                            </x-consumer.menu>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
