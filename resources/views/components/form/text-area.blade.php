@props(['label', 'name'])

<label class="block">
    <span class="font-semibold tracking-wide text-black text-md">
        {{ $label }}<span
            @class([
                'text-error text-base font-semibold leading-none',
                'hidden' => ! $attributes->get('required')
            ])
        >*</span>
    </span>
    <textarea
        class="form-textarea text-black w-full mt-1.5 resize-none rounded-lg border border-slate-300 bg-transparent p-2.5 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
        {{ $attributes }}
    ></textarea>
</label>

@error($name)
    <div class="mt-2">
        <span class="text-error text-sm+">
            {{ $message }}
        </span>
    </div>
@enderror
