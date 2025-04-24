@props(['name', 'label' => null])

<label class="inline-flex items-center space-x-2">
    <input
        type="checkbox"
        {{ $attributes->merge(['class' => 'form-switch h-5 w-10 rounded-full bg-slate-300 before:rounded-full before:bg-slate-50 checked:bg-primary checked:before:bg-white']) }}
    />
    <span>{{ $label }}</span>
</label>

@error($name)
    <div class="mt-2">
        <span class="text-error text-sm+">
            {{ $message }}
        </span>
    </div>
@enderror
