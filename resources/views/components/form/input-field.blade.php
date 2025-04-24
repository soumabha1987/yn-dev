@props([
    'label' => '',
    'instruction' => '',
    'type',
    'name',
])

<label class="block">
    @if (filled($label))
        <div class="flex flex-col lg:flex-row gap-1">
            <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                {{ $label }}<span
                    @class([
                        'text-error text-base leading-none',
                        'hidden' => ! $attributes->get('required')
                    ])
                >*</span>
            </span>
            {{ $instruction }}
        </div>
    @endif
    <input
        {{ $attributes->merge(['class' => "form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"]) }}
        type="{{ $type }}"
        autocomplete="off"
    >
</label>

@error($name)
    <div>
        <span class="text-error text-sm+">
            {{ $message }}
        </span>
    </div>
@enderror
