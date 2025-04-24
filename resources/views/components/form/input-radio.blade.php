@props([
    'label',
    'labelClass' => '',
])

<label class="inline-flex items-center space-x-2">
    <input
        type="radio"
        {{ $attributes->merge(['class' => 'form-radio is-basic size-4 sm:size-4.5 rounded-full border-slate-400/70 checked:border-primary checked:bg-primary hover:border-primary focus:border-primary']) }}
    >
    <span class="pr-10 {{ $labelClass }}">{{ $label }}</span>
</label>
