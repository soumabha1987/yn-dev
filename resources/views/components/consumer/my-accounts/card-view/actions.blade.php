@use('App\Enums\ConsumerStatus')

@props([
    'account',
    'type',
])

@php
    $stateName = array_flip(ConsumerStatus::sortingPriorityByStates())[$account->state_priority];
    $stateDetails = ConsumerStatus::consumerStates()[$stateName];
    $tabLabel = $stateName === 'active_negotiation' ? ($account->counter_offer ? __('Counter Offer Received') : __('Pending Creditor Response')) : $stateDetails['card_title'];
@endphp

<div>
    <div @class([
        'w-full mt-2 px-4 pb-5 sm:px-5' => $type === 'button',
    ])>
        @if ($account->accountConditions === 'joined')
            <a
                wire:navigate
                href="{{ route('consumer.negotiate', ['consumer' => $account->id]) }}"
                @class([
                    'btn space-x-2 w-full px-0 font-semibold sm:text-lg text-purple-600 hover:outline-2 hover:outline-purple-600 outline outline-[#2F3C4633] focus:outline-[#2F3C4633]' => $type === 'button',
                    "tag cursor-pointer text-sm rounded-full px-4 xl:text-nowrap {$stateDetails['card_tag_class']}" => $type === 'tab',
                ])
            >
                {{ $type === 'button' ? __('View My Offers!') : $tabLabel }}
            </a>
        @elseif ($account->accountConditions === 'deactivated' && $account->transactions_count > 0)
            <a
                wire:navigate
                href="{{ route('consumer.payment_history', ['consumer' => $account->id]) }}"
                @class([
                    'btn space-x-2 w-full px-0 font-semibold sm:text-lg text-purple-600 hover:outline-2 hover:outline-purple-600 outline outline-[#2F3C4633] focus:outline-[#2F3C4633]' => $type === 'button',
                    "tag cursor-pointer text-sm rounded-full px-4 xl:text-nowrap {$stateDetails['card_tag_class']}" => $type === 'tab',
                ])
            >
                {{ $type === 'button' ? __('View Pay History') : $tabLabel }}
            </a>
        @elseif (in_array($account->accountConditions, ['approved_but_payment_setup_is_pending', 'approved_settlement_but_payment_setup_is_pending']))
            <a
                wire:navigate
                href="{{ route('consumer.payment', ['consumer' => $account->id]) }}"
                @class([
                    'btn space-x-2 w-full px-0 font-semibold sm:text-lg text-purple-600 hover:outline-2 hover:outline-purple-600 outline outline-[#2F3C4633] focus:outline-[#2F3C4633]' => $type === 'button',
                    "tag cursor-pointer text-sm rounded-full px-4 xl:text-nowrap {$stateDetails['card_tag_class']}" => $type === 'tab',
                ])
            >
                {{ $type === 'button' ? __('Add My Payment') : $tabLabel }}
            </a>
        @elseif (in_array($account->accountConditions, ['payment_accepted_and_plan_in_scheduled', 'hold']))
            <a
                wire:navigate
                href="{{ route('consumer.schedule_plan', ['consumer' => $account->id]) }}"
                @class([
                    'btn space-x-2 w-full px-0 font-semibold sm:text-lg text-purple-600 hover:outline-2 hover:outline-purple-600 outline outline-[#2F3C4633] focus:outline-[#2F3C4633]' => $type === 'button',
                    "tag cursor-pointer text-sm rounded-full px-4 xl:text-nowrap {$stateDetails['card_tag_class']}" => $type === 'tab',
                ])
            >
                {{ $type === 'button' ? __('Open Payment Plan') : $tabLabel }}
            </a>
        @elseif ($account->accountConditions === 'creditor_send_an_offer')
            <livewire:consumer.my-account.view-offer
                :consumer="$account"
                :key="str()->random(10)"
                :view="$type === 'button' ? 'card' : 'tab'"
                :$tabLabel
                :tab-classes="$stateDetails['card_tag_class']"
            />
        @elseif ($account->accountConditions === 'pending_creditor_response')
            <x-consumer.my-accounts.last-offer :$account>
                <button
                    type="button"
                    @class([
                        'btn space-x-2 w-full px-0 font-semibold sm:text-lg text-purple-600 hover:outline-2 hover:outline-purple-600 outline outline-[#2F3C4633] focus:outline-[#2F3C4633]' => $type === 'button',
                        "tag cursor-pointer text-sm rounded-full px-4 xl:text-nowrap {$stateDetails['card_tag_class']}" => $type === 'tab',
                    ])
                >
                    {{ $type === 'button' ? __('Open Last Offer') : $tabLabel }}
                </button>
            </x-consumer.my-accounts.last-offer>
        @elseif (in_array($account->accountConditions, ['renegotiate', 'declined']))
            <button
                type="button"
                wire:click="startOver({{ $account->id }})"
                wire:loading.attr="disabled"
                wire:target="startOver({{ $account->id }})"
                @class([
                    'btn space-x-2 w-full px-0 font-semibold sm:text-lg text-purple-600 hover:outline-2 hover:outline-purple-600 outline outline-[#2F3C4633] focus:outline-[#2F3C4633]' => $type === 'button',
                    "tag cursor-pointer text-sm rounded-full px-4 xl:text-nowrap {$stateDetails['card_tag_class']}" => $type === 'tab',
                ])
            >
                @if ($type === 'button')
                    <x-lucide-loader-2
                        wire:loading
                        wire:target="startOver({{ $account->id }})"
                        class="animate-spin size-5 mr-1"
                    />
                    <span class="!ml-0">{{ __('Start over') }}</span>
                @else
                    <span>{{ $tabLabel }}</span>
                @endif
            </button>
        @elseif ($account->accountConditions === 'settled')
            <a
                wire:navigate
                href="{{ route('consumer.complete_payment', ['consumer' => $account->id]) }}"
                @class([
                    'btn space-x-2 w-full px-0 font-semibold sm:text-lg text-purple-600 hover:outline-2 hover:outline-purple-600 outline outline-[#2F3C4633] focus:outline-[#2F3C4633]' => $type === 'button',
                    "tag cursor-pointer text-sm rounded-full px-4 xl:text-nowrap {$stateDetails['card_tag_class']}" => $type === 'tab',
                ])
            >
                {{ $type === 'button' ? __('View My Payments') : $tabLabel }}
            </a>
        @elseif ($account->accountConditions === 'not_paying')
            <button
                type="button"
                wire:click="restart({{ $account->id }})"
                wire:loading.attr="disabled"
                wire:target="restart({{ $account->id }})"
                @class([
                    'btn disabled:opacity-50 space-x-2 w-full px-0 font-semibold sm:text-lg text-purple-600 hover:outline-2 hover:outline-purple-600 outline outline-[#2F3C4633] focus:outline-[#2F3C4633]' => $type === 'button',
                    "tag cursor-pointer text-sm rounded-full px-4 xl:text-nowrap {$stateDetails['card_tag_class']}" => $type === 'tab',
                ])
            >
                <span class="!ml-0">{{ $type === 'button' ? __('Restart Negotiations') : $tabLabel }}</span>
            </button>
        @else
            @if ($type === 'tab')
                <span class="tag cursor-auto select-none text-sm rounded-full px-4 xl:text-nowrap {{ $stateDetails['card_tag_class'] }}">
                    {{ $tabLabel }}
                </span>
            @endif
        @endif
    </div>
</div>
