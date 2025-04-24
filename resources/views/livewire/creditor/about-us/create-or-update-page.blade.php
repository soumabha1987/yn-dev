<div>
    <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6 w-full">
        <form
            method="POST"
            wire:submit="createOrUpdate"
            autocomplete="off"
        >
            <div class="card px-3 py-4 sm:px-5">
                <div>
                    <div class="my-2 flex items-center justify-between">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Enter Content') }}<span class="text-error text-base">*</span>
                        </span>
                    </div>
                    <div>
                        <x-form.quill-editor
                            wire:model="form.content"
                            :name="$form->content"
                            :placeHolder="__('Enter Content')"
                            class="h-48"
                            alpine-variable-name="$wire.form.content"
                            form-input-name="form.content"
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
                        href="{{ route('creditor.dashboard') }}"
                        class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 w-28"
                    >
                        {{ __('Cancel') }}
                    </a>
                    <x-dialog>
                        <x-dialog.open>
                            <x-form.button
                                type="button"
                                variant="primary"
                                class="border focus:border-primary-focus font-medium w-28"
                            >
                                <div class="flex space-x-1 items-center">
                                    <x-heroicon-o-eye class="size-5 text-white"/>
                                    <span>{{ __('Preview') }}</span>
                                </div>
                            </x-form.button>
                        </x-dialog.open>

                        <x-dialog.panel size="2xl" class="h-64">
                            <x-slot name="heading">
                                {{ __('Preview') }}
                            </x-slot>
                            <div class="prose text-gray-700" x-html="$wire.form.content"></div>
                            <x-slot name="footer" class="mt-3">
                                <x-dialog.close>
                                    <x-form.default-button 
                                        type="button" 
                                        class="mt-3"
                                    >
                                        {{ __('Close') }}
                                    </x-form.default-button>
                                </x-dialog.close>
                            </x-slot>
                        </x-dialog.panel>
                    </x-dialog>
                    <x-form.button
                        type="submit"
                        variant="success"
                        wire:target="createOrUpdate"
                        wire:loading.class="opacity-50"
                        wire:loading.attr="disabled"
                        class="border focus:border-success-focus font-medium w-28"
                    >
                        <x-lucide-loader-2
                            wire:target="createOrUpdate"
                            wire:loading
                            class="animate-spin size-5 mr-2"
                        />
                        {{ $form->content ? __('Update') : __('Create') }}
                    </x-form.button>
                </div>
            </div>
        </form>
    </div>
</div>
