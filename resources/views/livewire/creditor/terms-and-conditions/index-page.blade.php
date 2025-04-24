<div>
    <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6 w-full">
        <form
            wire:submit="createOrUpdate"
            autocomplete="off"
        >
            <div class="card px-4 py-4 sm:px-5">
                <div>
                    <x-form.select
                        wire:model.live="form.subclient_id"
                        :label="__('Apply to')"
                        :options="$subclients"
                        name="form.subclient_id"
                        required
                    />
                </div>
                <div class="mt-2">
                    <div class="my-3 flex items-center justify-between">
                    <span class="tracking-wide font-semibold text-black lg:text-md">
                        {{ __('Terms & Conditions Template') }}<span class="text-error text-base">*</span>
                    </span>
                    </div>
                    <div>
                        <x-form.quill-editor
                            wire:model="form.content"
                            class="h-48"
                            :name="$form->content"
                            alpine-variable-name="$wire.form.content"
                            form-input-name="form.content"
                            :placeHolder="__('Enter Content')"
                        />
                        @error('form.content')
                            <div class="mt-2">
                                <span class="text-error text-sm+">
                                    {{ $message }}
                                </span>
                            </div>
                        @enderror
                    </div>
                </div>
                <div class="flex justify-center sm:justify-end space-x-2 mt-9">
                    <a
                        wire:navigate
                        href="{{ route('home') }}"
                        class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 w-28"
                    >
                        {{ __('Cancel') }}
                    </a>
                    <x-form.button
                        type="submit"
                        variant="primary"
                        class="border focus:border-primary-focus font-medium w-28 disabled:opacity-50"
                        wire:target="createOrUpdate"
                        wire:loading.attr="disabled"
                    >
                        <x-lucide-loader-2
                            wire:loading
                            wire:target="createOrUpdate"
                            class="size-5 animate-spin mr-2"
                        />
                        {{ $form->content ? __('Update') : __('Save') }}
                    </x-form.button>
                </div>
            </div>
        </form>
    </div>

    <livewire:creditor.terms-and-conditions.list-view />
</div>
