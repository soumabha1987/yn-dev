@props(['position' => 'bottom-start'])

<div
    x-menu:items
    x-transition:enter.origin.top.right
    x-anchor.{{ $position }}="document.getElementById($id('alpine-menu-button'))"
    {{ $attributes->merge(['class' => 'w-48 z-10 bg-white border border-gray-200 rounded-md shadow-md py-1 outline-none']) }}
>
    {{ $slot }}
</div>
