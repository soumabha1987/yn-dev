@props(['type', 'variant'])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "btn select-none text-white bg-$variant hover:bg-$variant-focus focus:bg-$variant-focus active:bg-$variant-focus/90"]) }}
>
    {{ $slot }}
</button>
