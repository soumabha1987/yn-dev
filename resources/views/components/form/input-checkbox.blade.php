@props([
    'label',
    'name' => ''
])

<div>
    <label class="inline-flex space-x-2 items-center">
        <input
            type="checkbox"
            {{ $attributes->merge(['class' => 'form-checkbox is-basic size-4 sm:size-4.5 rounded border-slate-400/70 checked:bg-primary hover:border-primary focus:border-primary']) }}
        >
        <span>{{ $label }}</span>
    </label>
</div>

@error($name)
    <div class="mt-2">
        <span class="text-error text-sm+">
            {{ $message }}
        </span>
    </div>
@enderror
