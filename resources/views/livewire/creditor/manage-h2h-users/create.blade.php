<div>
    <template x-if="! visible">
        <x-dialog wire:model="openModel">
            <x-dialog.open>
                <x-form.button
                    type="button"
                    variant="primary"
                    class="space-x-1"
                >
                    <x-lucide-circle-plus class="size-5" />
                    <span>{{ 'Create' }}</span>
                </x-form.button>
            </x-dialog.open>
            <x-dialog.panel>
                <x-slot name="heading">
                    {{ __('Create User') }}
                </x-slot>
                <form
                    @close-dialog-box.window="() => {
                        dialogOpen = false
                        $dispatch('refresh-page')
                    }"
                    method="POST"
                    wire:submit="create"
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
                    <div class="mt-2">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold tracking-wide text-black line-clamp-1 lg:text-md">
                                {{ __('Password') }}<span class="text-error text-base">*</span>
                            </h2>
                        </div>
                        <x-auth.password
                            wire:model="form.password"
                            name="form.password"
                            :hasIcon="false"
                            label-class="!mt-2"
                            class="border border-slate-300 bg-transparent px-3 py-2 pr-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary"
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
                            wire:target="create"
                            wire:loading.attr="disabled"
                            class="border focus:border-primary-focus disabled:opacity-50"
                        >
                            <x-lucide-loader-2
                                class="size-4.5 sm:size-5 animate-spin"
                                wire:target="create"
                                wire:loading
                            />
                            {{ __('Create') }}
                        </x-form.button>
                    </div>
                </form>
            </x-dialog.panel>
        </x-dialog>
    </template>
</div>
