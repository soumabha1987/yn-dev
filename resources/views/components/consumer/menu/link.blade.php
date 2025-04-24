@props([
    'href',
    'icon',
    'title',
    'subTitle',
])

<a
    wire:navigate
    href="{{ $href }}"
    class="relative group flex items-center space-x-3 px-4 py-2 tracking-wide outline-none transition-all hover:bg-slate-100 focus:bg-slate-100"
>
    <div {{ $attributes->merge(['class' => "flex size-8 items-center justify-center rounded-lg text-white"]) }}>
        {{ $icon }}
    </div>
    <div>
        <h2 class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary">
            {{ $title }}
        </h2>
        <div class="line-clamp-1 text-xs text-slate-400">
            {{ $subTitle }}
        </div>
    </div>
</a>
