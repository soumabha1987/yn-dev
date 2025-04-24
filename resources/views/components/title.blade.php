@props(['title', 'subtitle'])

<div class="flex items-baseline space-x-1">
    <h2 class="text-black">
        {{ $title }}
    </h2>
    <span class="text-xs text-slate-700">
        {{ $subtitle }}
    </span>
</div>
