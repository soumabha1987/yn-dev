@use('App\Enums\AutomatedTemplateType')
@use('App\Enums\TemplateCustomField')

<div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6 w-full">
    <form
        x-data="{ emailSubject: '', content: '' }"
        method="POST"
        wire:submit="create"
        autocomplete="off"
    >
        <div class="card py-4 px-4 sm:px-5">
            <div>
                <x-form.input-field
                    type="text"
                    wire:model="form.name"
                    class="w-full"
                    :label="__('Template Name')"
                    :placeholder="__('Enter Template Name')"
                    name="form.name"
                    required
                />
            </div>
            <div class="mt-5">
                <div class="my-3 flex items-center justify-between">
                    <h2 class="font-semibold tracking-wide text-black lg:text-md">
                        {{ __('Template Type') }}<span class="text-error text-base">*</span>
                    </h2>
                </div>

                <div x-data="{ templateType: @js(AutomatedTemplateType::EMAIL->value) }">
                    <x-form.input-radio
                        :label="__('Email')"
                        wire:model="form.type"
                        x-modelable="templateType"
                        x-on:click="templateType = '{{ AutomatedTemplateType::EMAIL->value }}'"
                        :value="AutomatedTemplateType::EMAIL->value"
                    />

                    <x-form.input-radio
                        :label="__('SMS')"
                        wire:model="form.type"
                        x-modelable="templateType"
                        x-on:click="() => {
                            templateType = '{{ AutomatedTemplateType::SMS->value }}'
                            $dispatch('clear-subject')
                            $wire.form.content = ''
                        }"
                        :value="AutomatedTemplateType::SMS->value"
                    />

                    @error('form.type')
                        <div class="mt-2">
                            <span class="text-error text-sm+">
                                {{ $message }}
                            </span>
                        </div>
                    @enderror

                    <template x-if="templateType === @js(AutomatedTemplateType::EMAIL->value)">
                        <div @clear-subject.window="emailSubject = null">
                            <div>
                                <div class="mt-5">
                                    <div class="my-1.5 flex items-center justify-between">
                                        <h2 class="font-medium capitalize tracking-wide text-black lg:text-sm+">
                                            {{ __('Select custom data for subject') }}
                                        </h2>
                                    </div>
                                </div>
                                <p>
                                    @foreach(TemplateCustomField::values() as $customField)
                                        <button
                                            type="button"
                                            class="inline-block mb-1 mr-2"
                                            x-on:click="emailSubject += ' ' + $el.innerHTML.trim()"
                                        >
                                            {{ $customField }}
                                        </button>
                                    @endforeach
                                </p>
                            </div>
                            <div class="mt-5">
                                <x-form.input-field
                                    class="w-full"
                                    :label="__('Subject')"
                                    type="text"
                                    placeholder="{{ __('Email subject') }}"
                                    required
                                    name="form.subject"
                                    wire:model="form.subject"
                                    x-on:input="emailSubject = $el.value"
                                    x-modelable="emailSubject"
                                />
                            </div>
                            <div class="mt-5">
                                <div>
                                    <div class="mt-5">
                                        <div class="my-1.5 flex items-center justify-between">
                                            <h2 class="font-medium capitalize tracking-wide text-black lg:text-sm+">
                                                {{ __('Select custom data for content') }}
                                            </h2>
                                        </div>
                                    </div>
                                    <p>
                                        @foreach(TemplateCustomField::values() as $customField)
                                            <button
                                                type="button"
                                                class="inline-block mb-1 mr-2"
                                                @click="$dispatch('update-content', { content: content + ' ' + $el.innerHTML.trim() })"
                                            >
                                                {{ $customField }}
                                            </button>
                                        @endforeach
                                    </p>
                                </div>
                                <div class="mt-5">
                                    <div class="my-1.5 flex items-center justify-between">
                                        <h2 class="font-semibold tracking-wide text-black lg:text-md">
                                            {{ __('Template Content') }}<span class="text-error text-base">*</span>
                                        </h2>
                                    </div>
                                    <div>
                                        <x-form.quill-editor
                                            class="h-48"
                                            :name="$form->content"
                                            alpine-variable-name="content"
                                            form-input-name="form.content"
                                            :placeHolder="__('Enter Content')"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="templateType === '{{ AutomatedTemplateType::SMS->value }}'">
                        <div>
                            <div>
                                <div class="mt-5">
                                    <div class="my-3 flex items-center justify-between">
                                        <h2 class="font-medium capitalize tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                            {{ __('Select custom data for content') }}
                                        </h2>
                                    </div>
                                </div>
                                <p class="mb-5">
                                    @foreach(TemplateCustomField::values() as $customField)
                                        <button
                                            type="button"
                                            class="inline-block mb-1 mr-2"
                                            x-on:click="() => {
                                                var textarea = document.querySelector('textarea')
                                                var startPosition = textarea.selectionStart
                                                var endPosition = textarea.selectionEnd
                                                textarea.value = textarea.value.substring(0, startPosition) + $el.innerHTML.trim() + textarea.value.substring(endPosition, textarea.value.length)
                                                textarea.selectionStart = textarea.selectionEnd = textarea.value.length
                                                $wire.form.content = textarea.value
                                                textarea.focus()
                                            }"
                                        >
                                            {{ $customField }}
                                        </button>
                                    @endforeach
                                </p>
                            </div>
                            <x-form.text-area
                                :label="__('Enter Message')"
                                name="form.content"
                                wire:model="form.content"
                                required
                            />
                        </div>
                    </template>
                    <div class="flex justify-center sm:justify-end space-x-2 mt-9">
                        <a
                            wire:navigate
                            href="{{ route('super-admin.automated-templates') }}"
                            class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 min-w-[7rem]"
                        >
                            {{ __('Cancel') }}
                        </a>
                        <x-form.button
                            type="submit"
                            variant="primary"
                            wire:target="create"
                            wire:loading.attr="disabled"
                            class="font-medium min-w-[7rem] border focus:border-primary-focus disabled:opacity-50"
                        >
                            <x-lucide-loader-2
                                wire:target="create"
                                wire:loading
                                class="animate-spin size-5 mr-2"
                            />
                            {{ __('Submit') }}
                        </x-form.button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
