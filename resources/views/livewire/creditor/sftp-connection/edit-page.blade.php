<div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6 w-full">
    <form
        method="POST"
        x-data="updateUsedFor"
        x-on:submit="update"
        autocomplete="off"
    >
        <div class="card py-4 px-4 sm:px-5">
            <div class="grid grid-cols-1 gap-5 items-baseline sm:grid-cols-3">
                <div>
                    <x-form.input-field
                        wire:model="form.name"
                        type="text"
                        name="form.name"
                        :label="__('Name')"
                        :placeholder="__('Enter name')"
                        class="w-full"
                        required
                    />
                </div>
                <div>
                    <x-form.input-field
                        wire:model="form.host"
                        type="text"
                        name="form.host"
                        :label="__('Host')"
                        :placeholder="__('Enter host')"
                        class="w-full"
                        required
                    />
                </div>
                <div>
                    <x-form.input-field
                        wire:model="form.port"
                        type="text"
                        name="form.port"
                        :label="__('Port')"
                        :placeholder="__('Enter port')"
                        class="w-full"
                        required
                    />
                </div>
                <div>
                    <x-form.input-field
                        wire:model="form.username"
                        type="text"
                        name="form.username"
                        :label="__('Username')"
                        :placeholder="__('Enter username')"
                        class="w-full"
                        required
                    />
                </div>
                <div>
                    <div class="flex items-center justify-between mb-0.5">
                        <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Password') }}<span class="text-error text-base">*</span>
                        </span>
                    </div>
                    <x-form.input-password
                        wire:model="form.password"
                        :label="__('Password')"
                        name="form.password"
                        :placeholder="__('Enter password')"
                    />
                </div>
                <div>
                    <label class="font-semibold tracking-wide text-black lg:text-md">
                        SFTP Type
                        <span class="text-error text-base">*</span>
                    </label>
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-3.5 sm:gap-0.5 mt-2 sm:mt-0.5 xl:mt-2">
                        <x-form.input-radio
                            wire:model="form.used_for"
                            :label="__('Import only')"
                            name="form.used_for"
                            value="import"
                        />
                        <x-form.input-radio
                            wire:model="form.used_for"
                            :label="__('Export only')"
                            name="form.used_for"
                            value="export"
                        />
                        <x-form.input-radio
                            wire:model="form.used_for"
                            :label="__('Both')"
                            name="form.used_for"
                            value="both"
                        />
                    </div>
                </div>
                <template x-if="['import', 'both'].includes($wire.form.used_for)">
                    <div>
                        <x-form.input-field
                            wire:model="form.import_filepath"
                            type="text"
                            name="form.import_filepath"
                            :label="__('Import folder path')"
                            :placeholder="__('Enter import folder path')"
                            class="w-full"
                            required
                        />
                    </div>
                </template>
                <template x-if="['export', 'both'].includes($wire.form.used_for)">
                    <div>
                        <x-form.input-field
                            wire:model="form.export_filepath"
                            type="text"
                            name="form.export_filepath"
                            :label="__('Export folder path')"
                            :placeholder="__('Enter export folder path')"
                            class="w-full"
                            required
                        />
                    </div>
                </template>
            </div>
            <div class="flex justify-center sm:justify-end space-x-2 mt-9">
                <a
                    wire:navigate
                    href="{{ route('creditor.sftp') }}"
                    class="btn border focus:border-slate-400 bg-slate-150 w-32 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                >
                    {{ __('Cancel') }}
                </a>
                <x-form.button
                    type="submit"
                    variant="primary"
                    wire:target="update"
                    wire:loading.attr="disabled"
                    wire:loading.class="disabled:opacity-50"
                    class="font-medium w-32 border focus:border-primary-focus"
                >
                    <div
                        wire:loading
                        wire:target="update"
                    >
                        {{ __('Updating') }}
                    </div>
                    <div
                        wire:target="update"
                        wire:loading.remove
                    >
                        {{ __('Update') }}
                    </div>
                </x-form.button>
            </div>
        </div>
    </form>
    @script
        <script>
            Alpine.data('updateUsedFor', () => {
                return {
                    update() {
                        this.$event.preventDefault()

                        if (this.$wire.form.used_for === 'export') {
                            this.$wire.form.import_filepath = ''
                        }

                        if (this.$wire.form.used_for === 'import') {
                            this.$wire.form.export_filepath = ''
                        }

                        this.$wire.update()
                    }
                }
            })
        </script>
    @endscript
</div>
