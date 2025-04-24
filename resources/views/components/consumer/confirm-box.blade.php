@props([
    'needDialogPanel' => true,
    'svg' => null,
    'buttons' => null,
    'heading' => __('Are you sure?'),
    'message' => __('Are you sure you want to delete this'),
    'cancelButtonLabel' => __('Cancel'),
    'okButtonLabel' => __('Okay'),
    'size' => 'lg',
])

<div>
    <x-consumer.dialog>
        <span @close-confirmation-box.window="dialogOpen = false" />
        <x-consumer.dialog.open>
            {{ $slot }}
        </x-consumer.dialog.open>
        <x-consumer.dialog.panel
            :$size
            :$needDialogPanel
            :confirm-box="true"
        >
            @if ($svg)
                <x-slot name="svg">{{ $svg }}</x-slot>
            @endif
            <x-slot name="heading">
                {{ $heading }}
            </x-slot>
            <x-slot name="message">
                {{ $message }}
            </x-slot>

            @if ($buttons)
                <x-slot name="buttons">{{ $buttons }}</x-slot>
            @else
                <x-slot name="buttons">
                    <div class="flex justify-center items-center space-x-2">
                        <x-consumer.dialog.close>
                            <x-form.default-button type="button">
                                {{ $cancelButtonLabel }}
                            </x-form.default-button>
                        </x-consumer.dialog.close>
                        <x-consumer.form.button
                            type="button"
                            variant="error"
                            {{ $attributes->merge(['class' => 'border focus:border-error-focus']) }}
                        >
                            {{ $okButtonLabel }}
                        </x-consumer.form.button>
                    </div>
                </x-slot>
            @endif

        </x-consumer.dialog.panel>
    </x-consumer.dialog>
</div>
