@props([
    'name' => null,
    'label' => null,
    'options',
    'blankOption' => [],
])

<label class="block">
    <div class="flex items-center">
        @if ($label !== null)
            <span class="font-semibold tracking-wide text-black lg:text-md">
                {{ $label }}<span
                    @class([
                        'text-error text-base leading-none',
                        'hidden' => ! $attributes->get('required')
                    ])
                >*</span>
            </span>
        @endif
    </div>
    <select
        {{ $attributes->merge(['class' => 'form-select capitalize mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 invalid:text-slate-500 hover:border-slate-400 focus:border-primary']) }}
    >
        @if (blank($blankOption))
            <option value="">{{ __('Select ' . $attributes->get('placeholder')) }}</option>
        @else
            {{ $blankOption }}
        @endif
        @foreach($options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</label>

@error($name)
    <div class="mt-2">
        <span class="text-error text-sm+">
            {{ $message }}
        </span>
    </div>
@enderror
