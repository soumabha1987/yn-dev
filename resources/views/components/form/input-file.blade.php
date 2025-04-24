@props(['name'])

<label
    class="btn relative bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
    wire:target="{{ $name }}"
    wire:loading.class="opacity-50"
>
    <input
        tabindex="-1"
        type="file"
        class="pointer-events-none absolute inset-0 size-full opacity-0"
        {{ $attributes }}
    >
    <span class="flex items-center space-x-2">
        <x-lucide-cloud-upload
            wire:loading.remove
            wire:target="{{ $name }}"
            class="size-5 text-base"
        />
        <x-lucide-loader-2
            wire:loading
            wire:target="{{ $name }}"
            class="size-5 animate-spin"
        />
        <span>{{ __('Choose Profile Picture') }}</span>
    </span>
</label>

@error($name)
    <div class="mt-1">
        <span class="text-xs+ text-error">
            {{ $message }}
        </span>
    </div>
@enderror
