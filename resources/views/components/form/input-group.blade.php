@props([
    'label' => null,
    'icon',
    'iconRight' => null,
    'type',
    'name',
    'iconPosition' => 'left'
])

@php
    $inputClass = $iconPosition === 'left' ? 'rounded-r-lg' : 'rounded-l-lg';
    $inputClass .= $iconRight ? ' !rounded-none' : '';
@endphp

<div>
    @if ($label)
        <label for="{{ $name }}" class="font-semibold tracking-wide text-black text-md">
            {{ $label }}<span
                @class([
                    'text-error text-base leading-none',
                    'hidden' => ! $attributes->get('required')
                ])
            >*</span>
        </label>
    @endif

    <div class="mt-1.5 flex -space-x-px">
        @if ($iconPosition === 'left')
            <div class="flex items-center justify-center rounded-l-lg border border-slate-300 bg-slate-150 px-3.5 text-slate-800">
                <span>{{ $icon }}</span>
            </div>
        @endif

        <input
            id="{{ $name }}"
            {{ $attributes->merge(['class' => "form-input w-full border border-slate-300 $inputClass bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary"]) }}
            type="{{ $type }}"
            autocomplete="off"
        >
        @if ($iconPosition === 'right')
            <div class="flex items-center justify-center rounded-r-lg border border-slate-300 bg-slate-150 px-3.5 text-slate-800">
                <span>{{ $icon }}</span>
            </div>
        @endif
        @if ($iconRight)
            <div class="flex items-center justify-center rounded-r-lg border border-slate-300 bg-slate-150 px-3.5 text-slate-800">
                <span>{{ $iconRight }}</span>
            </div>
        @endif
    </div>
</div>

@error($name)
    <div class="mt-2">
        <span class="text-error text-sm+">
            {{ $message }}
        </span>
    </div>
@enderror
