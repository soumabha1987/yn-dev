@props([
    'needDialogPanel' => true,
    'confirmBox' => false,
    'blankPanel' => false,
    'size' => 'lg',
    'height',
    'heading' => '',
    'headerCancelIcon' => false,
    'svg' => null
])

<template x-teleport="body">
    <div
        x-dialog
        x-model="dialogOpen"
        class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5"
    >
        <div
            x-dialog:overlay
            class="absolute inset-0 bg-slate-900/60 transition-opacity"
            x-transition:enter="ease-out duration-100"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <div
            @if ($needDialogPanel)
                x-dialog:panel
            @else
                x-show="dialogOpen"
            @endif
            @class([
                'relative w-full origin-top rounded-lg bg-white shadow-xl transition-all',
                'max-w-sm' => $size === 'sm',
                'max-w-md' => $size === 'md',
                'max-w-lg' => $size === 'lg',
                'max-w-xl' => $size === 'xl',
                'max-w-2xl' => $size === '2xl',
                'max-w-3xl' => $size === '3xl',
                'max-w-4xl' => $size === '4xl',
                'max-w-5xl' => $size === '5xl',
            ])
            x-transition:enter="ease-out duration-100 transform"
            x-transition:enter-start="opacity-0 scale-95 translate-y-8"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-100 transform"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-8"
        >
            @if ($confirmBox)
                <div class="text-center px-4 py-8">
                    @if ($headerCancelIcon)
                        {{ $headerCancelIcon }}
                    @endif

                    @if ($svg ?? false)
                        {{ $svg }}
                    @endif

                    <div @class([
                            'mt-4' => blank($svg) || blank($headerCancelIcon),
                        ])
                    >
                        <h2 class="text-lg lg:text-2xl text-black font-semibold">
                            {{ $heading ?? 'Success Message' }}
                        </h2>
                        <p class="mt-2 text-black">
                            {{ $message ?? '' }}
                        </p>
                        <div class="mt-4">{{ $buttons ?? '' }}</div>
                    </div>
                    
                </div>
            @elseif ($blankPanel)
                {{ $slot }}
            @else
                <div class="flex justify-between items-center rounded-t-lg bg-gradient-to-r from-blue-700 to-blue-500 text-white px-4 py-3 sm:px-5">
                    <h3 class="text-base lg:text-xl font-bold tracking-wide">
                        {{ $heading ?? '' }}
                    </h3>
                    <button
                        type="button"
                        x-on:click.stop="$dialog.close()"
                        class="btn size-6 lg:size-10 text-white rounded-full p-0 mr-1 hover:bg-slate-300/20"
                    >
                        <x-heroicon-o-x-mark class="size-8 fill-white text-white" />
                    </button>
                </div>
                <div class="px-4 py-4 sm:px-5">
                    <div {{ $attributes->merge(['class' => 'max-h-[60vh] lg:max-h-[70vh] xl:max-h-[80vh] overflow-y-scroll scroll-bar-visible']) }}>
                        {{ $slot }}
                    </div>
                    @if ($footer ?? false)
                        <div {{ $footer->attributes->merge(['class' => 'space-x-2 text-right']) }}>
                            {{ $footer }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</template>
