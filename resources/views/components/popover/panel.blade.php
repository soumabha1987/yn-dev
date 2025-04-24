@props(['position' => 'bottom-center'])

<div
    x-popover:panel
    x-transition.out.opacity
    x-anchor.{{ $position }}.offset.10="document.getElementById($id('alpine-popover-button'))"
    {{ $attributes->merge(['class' => 'absolute left-0 mt-2 bg-white z-10 rounded-md']) }}
>
    {{ $slot }}
</div>
