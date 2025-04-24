@props(['color' => 'slate'])

<button
    {{ $attributes->merge(['class' => "btn bg-$color-150 font-medium border focus:border-$color-400 text-$color-800 hover:bg-$color-200 focus:bg-$color-200 active:bg-$color-200/80"]) }}
>
    {{ $slot }}
</button>
