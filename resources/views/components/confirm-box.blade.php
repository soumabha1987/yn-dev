@props([
    'heading' => __('Confirm?'),
    'message' => __('Are you sure you want to delete this'),
    'cancelButtonLabel' => __('Cancel'),
    'okButtonLabel' => __('Okay'),
    'action',
    'isLoading' => false,
    'okButtonVariant' => 'primary',
    'svg' => null,
])

<div>
    <x-dialog>
        <span x-on:close-confirmation-box.window="dialogOpen = false" />
        <x-dialog.open>
            {{ $slot }}
        </x-dialog.open>
        <x-dialog.panel 
            :confirm-box="true"
            size="sm"
        >
            <x-slot name="svg">
                {{ $svg }}
            </x-slot>
            <x-slot name="heading">
                {{ $heading }}
            </x-slot>
            <x-slot name="message">
                {{ $message }}
            </x-slot>
            <x-slot name="buttons">
                <x-dialog.close>
                    <x-form.default-button 
                        type="button" 
                        class="w-32"
                    >
                        {{ $cancelButtonLabel }}
                    </x-form.default-button>
                </x-dialog.close>
                @if ($isLoading)
                    <x-form.button
                        x-on:click="dialogOpen = false"
                        wire:click="{{ $action }}"
                        variant="{{ $okButtonVariant }}"
                        type="button"
                        class="ml-2 w-32 border focus:border-primary-focus"
                        wire:loading.attr="disabled"
                        wire:loading.class="disabled:opacity-50"
                    >
                        <span
                            wire:loading
                            wire:target="{{ $action }}"
                        >
                            {{ __('Submitting..') }}
                        </span>
                        <span
                            wire:target="{{ $action }}"
                        >
                            {{ $okButtonLabel }}
                        </span>
                    </x-form.button>
                @else
                    <x-form.button
                        x-on:click="dialogOpen = true"
                        type="button"
                        variant="error"
                        class="ml-2 w-32 border focus:border-error-focus"
                        wire:click="{{ $action }}"
                    >
                        {{ $okButtonLabel }}
                    </x-form.button>
                @endif
            </x-slot>
        </x-dialog.panel>
    </x-dialog>
</div>
