@php
    $gradientColors = [
        'black' => 'bg-gradient-to-r from-gray-700 via-gray-900 to-black',
        'blue' => 'bg-gradient-to-r from-blue-400 to-blue-700',
        'green' => 'bg-gradient-to-r from-emerald-500 to-lime-600',
        'yellow' => 'bg-gradient-to-r from-yellow-200 via-yellow-300 to-yellow-400',
        'orange' => 'bg-gradient-to-r from-yellow-600 to-red-600',
        'purple' => 'bg-gradient-to-r from-purple-800 via-violet-900 to-purple-800',
        'red' => 'bg-[conic-gradient(at_left,_var(--tw-gradient-stops))] from-rose-900 via-amber-800 to-rose-400'
    ];
@endphp

@props([
    'message',
    'color',
    'svg' => null,
    'closeButton' => true,
])

<div
    x-data="{ showAlert: $persist(true).as('show-alert') }"
    x-modelable="showAlert"
    x-show="showAlert"
    {{ $attributes->merge(['class' => "alert sticky top-0 z-50 items-center w-full $gradientColors[$color] justify-between overflow-hidden text-white"]) }}
>
    <div class="flex items-center">
        <div class="px-4 py-1.5 w-full text-center sm:px-5">
            {{ $svg }}
            <span class="mx-2 tracking-wide font-semibold">
                {{ $message }}
            </span>

            {{ $action ?? '' }}
        </div>

        <div
            class="px-2 justify-end"
            x-show="@js($closeButton)"
        >
            <button
                x-on:click="showAlert = false"
                class="btn size-7 rounded-full p-0 font-medium text-white hover:bg-white/20 focus:bg-white/20 active:bg-white/25"
            >
                <x-lucide-x class="size-4.5" />
            </button>
        </div>
    </div>
</div>
