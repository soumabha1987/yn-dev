@props([
    'title',
    'icon',
])

<div class="group flex items-center space-x-3 px-4 py-2 tracking-wide outline-none cursor-not-allowed relative">
    <div class="flex size-8 items-center justify-center rounded-lg bg-primary-light text-white">
        {{ $icon }}
    </div>
    <div>
        <h2 class="font-medium text-slate-700 transition-colors">
            {{ $title }}
        </h2>
        <div class="line-clamp-1 text-xs text-slate-400">
            <span class="blink rounded-full border-error font-semibold text-primary">{{ __('Coming Soon...') }}</span>
        </div>
    </div>
</div>
