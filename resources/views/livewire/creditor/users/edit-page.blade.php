<div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6 w-full">
    <form method="POST" wire:submit="update" autocomplete="off">
        <div class="card py-4 px-4 sm:px-5 lg:w-1/2">
            <div class="grid grid-cols-1 gap-5 items-baseline sm:grid-cols-2">
                <div>
                    <x-form.input-field
                        wire:model="form.first_name"
                        :label="__('First Name')"
                        type="text"
                        name="form.first_name"
                        :placeholder="__('Enter First Name')"
                        class="w-full"
                        required
                    />
                </div>
                <div>
                    <x-form.input-field
                        wire:model="form.last_name"
                        :label="__('Last Name')"
                        type="text"
                        name="form.last_name"
                        :placeholder="__('Enter Last Name')"
                        class="w-full"
                        required
                    />
                </div>
                <div class="mt-5">
                    <x-form.input-field
                        wire:model="form.email"
                        :label="__('Email')"
                        type="text"
                        name="form.email"
                        :placeholder="__('Enter Email')"
                        class="w-full"
                        required
                    />
                </div>
            </div>
            <div class="flex justify-center sm:justify-end space-x-2 mt-9">
                <a
                    wire:navigate
                    href="{{ route('creditor.users') }}"
                    class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 w-28"
                >
                    {{ __('Cancel') }}
                </a>
                <x-form.button
                    type="submit"
                    variant="primary"
                    wire:target="update"
                    wire:loading.attr="disabled"
                    class="border focus:border-primary-focus font-medium w-28 disabled:opacity-50"
                >
                    <x-lucide-loader-2
                        wire:loading
                        wire:target="update"
                        class="size-5 animate-spin mr-2"
                    />
                    {{ __('Update') }}
                </x-form.button>
            </div>
        </div>
    </form>
</div>

