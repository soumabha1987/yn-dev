<div>
    <x-dialog>
        <x-form.button
            type="button"
            variant="info"
            class="text-xs sm:text-sm space-x-1 px-2 sm:px-3 py-1.5 btn text-white"
            x-on:click="dialogOpen = true"
        >
            <x-heroicon-o-pencil-square class="size-4.5 sm:size-5" />
            <span>{{ 'Edit' }}</span>
        </x-form.button>

        <x-dialog.panel>
            <x-slot name="heading">
                {{ __('Update User') }}
            </x-slot>

            <form
                method="POST"
                wire:submit="update"
                @close-dialog-box.window="() => {
                    dialogOpen = false
                    $dispatch('refresh-page')
                }"
                autocomplete="off"
            >
                <div>
                    <x-form.input-field
                        wire:model="form.name"
                        type="text"
                        name="form.name"
                        :label="__('Name')"
                        :placeholder="__('Enter Name')"
                        class="w-full"
                        required
                    />
                </div>
                <div class="mt-2">
                    <x-form.input-field
                        wire:model="form.email"
                        type="email"
                        name="form.email"
                        :label="__('Email')"
                        :placeholder="__('Enter Email')"
                        class="w-full"
                        required
                    />
                </div>
                <div class="mt-2">
                    <x-form.us-phone-number
                        name="form.phone_no"
                        :label="__('Phone')"
                        :placeholder="__('Enter Phone Number')"
                        autocomplete="off"
                        required
                    />
                </div>
                <div class="flex mt-5 space-x-2 justify-end">
                    <x-dialog.close>
                        <x-form.default-button type="button">
                            {{ __('Cancel') }}
                        </x-form.default-button>
                    </x-dialog.close>
                    <x-form.button
                        type="submit"
                        variant="primary"
                        wire:target="update"
                        wire:loading.attr="disabled"
                        class="border focus:border-primary-focus disabled:opacity-50"
                    >
                        <x-lucide-loader-2
                            wire:target="update"
                            wire:loading
                            class="animate-spin size-5 mr-2"
                        />
                        {{ __('Update') }}
                    </x-form.button>
                </div>
            </form>
        </x-dialog.panel>
    </x-dialog>
</div>
