@props([
    'description' => null,
    'position' => 'bottom',
])

<label class="relative flex">
    <input
        type="text"
        @if ($description)
            x-tooltip.placement.{{ $position }}="@js($description)"
        @endif
        {{ $attributes->merge(['class' => 'form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary']) }}
        autocomplete="off"
    >
    <div class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary">
        <x-heroicon-o-magnifying-glass class="size-4.5 transition-colors duration-200" />
    </div>
</label>
