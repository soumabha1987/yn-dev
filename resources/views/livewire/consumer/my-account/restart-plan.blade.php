<div>
    <x-consumer.confirm-box
        :message="__('This will restart the payment plan immediately and remove the hold.')"
    >
        <x-consumer.menu.item>
            @if ($page === 'schedule-plan')
                <x-lucide-circle-play class="size-5" />
            @endif
            <span>{{ __('Restart Plan Now') }}</span>
        </x-consumer.menu.item>
        <x-slot name="buttons">
            <div
                class="flex justify-center items-center space-x-2"
                x-on:close-dialog.window="() => {
                   dialogOpen = false;
                   $dispatch('refresh-page');
                }"
            >
                <x-consumer.dialog.close>
                    <x-form.default-button type="button">
                        {{ __('Cancel') }}
                    </x-form.default-button>
                </x-consumer.dialog.close>
                <x-consumer.form.button
                    wire:click="restartPlan"
                    wire:loading.attr="disabled"
                    wire:target="restartPlan"
                    type="button"
                    variant="primary border focus:border-primary-focus"
                >
                    {{ __('Restart') }}
                </x-consumer.form.button>
            </div>
        </x-slot>
    </x-consumer.confirm-box>
</div>
