@use('App\Enums\Role')
@use('App\Enums\TemplateType')

<div>
    <div class="card px-4 pb-4 sm:px-5">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6 w-full">
            <form
                x-data="eLetterTypes"
                method="POST"
                wire:submit="createOrUpdate"
                autocomplete="off"
                x-on:update-title.window="emailSubject = $wire.form.subject"
            >
                <div class="mt-5">
                    <x-form.input-field
                        type="text"
                        wire:model="form.name"
                        class="w-full"
                        name="form.name"
                        :label="__('Template Name')"
                        :placeholder="__('Enter Template Name')"
                        required
                    />
                </div>
                @hasrole(Role::SUPERADMIN)
                    <div class="mt-5">
                        <div>
                            @if ($form->template === null)
                                <div class="my-3 flex items-center justify-between">
                                    <h2 class="font-semibold tracking-wide text-black lg:text-md">
                                        {{ __('Template Type') }}<span class="text-error text-base">*</span>
                                    </h2>
                                </div>
                                <x-form.input-radio
                                    :label="__('Email')"
                                    wire:model="form.type"
                                    x-modelable="templateType"
                                    x-on:click="updateTemplateType"
                                    :value="TemplateType::EMAIL->value"
                                />
                                <x-form.input-radio
                                    :label="__('SMS')"
                                    wire:model="form.type"
                                    x-modelable="templateType"
                                    x-on:click="updateTemplateType"
                                    :value="TemplateType::SMS->value"
                                />
                                @error('form.type')
                                    <div class="mt-2">
                                        <span class="text-error text-sm+">
                                            {{ $message }}
                                        </span>
                                    </div>
                                @enderror
                            @endif
                            <template x-if="templateType === @js(TemplateType::EMAIL->value)">
                                <div>
                                    <x-communications.custom-field-buttons
                                        label="Select custom data for subject"
                                        x-on:click="appendCustomFieldsIntoSubject"
                                    />
                                    <div class="mt-5">
                                        <x-form.input-field
                                            wire:model="form.subject"
                                            x-on:input="emailSubject = $el.value"
                                            x-modelable="emailSubject"
                                            class="w-full"
                                            :label="__('Subject')"
                                            type="text"
                                            :placeholder="__('Email subject')"
                                            required
                                            name="form.subject"
                                        />
                                    </div>
                                    <div class="mt-5">
                                        <x-communications.custom-field-buttons
                                            label="Select custom data for content"
                                            x-on:click="updateDescriptionOnBackend"
                                        />
                                        <x-communications.content-editor
                                            :$form
                                            field="description"
                                            :label="__('Template Content')"
                                            :placeholder="__('Enter description')"
                                        />
                                    </div>
                                </div>
                            </template>
                            <template x-if="templateType === @js(TemplateType::SMS->value)">
                                <div>
                                    <x-communications.custom-field-buttons
                                        :label="__('Select custom data for content')"
                                        x-on:click="appendCustomFieldsIntoDescription"
                                    />
                                    <div class="mt-5">
                                        <x-form.text-area
                                            :label="__('Enter Message')"
                                            name="form.smsDescription"
                                            wire:model="form.smsDescription"
                                            required
                                        />
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                @else
                    <div class="mt-5">
                        <x-communications.custom-field-buttons
                            label="Select custom data for content"
                            x-on:click="updateDescriptionOnBackend"
                        />
                        <x-communications.content-editor
                            :$form
                            field="description"
                            :label="__('Template Content')"
                            :placeholder="__('Enter description')"
                        />
                    </div>
                @endhasrole
                <div class="flex space-x-2 justify-center sm:justify-end mt-9">
                    <a
                        wire:navigate
                        href="{{ route('home') }}"
                        class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                    >
                        {{ __('Cancel') }}
                    </a>
                    <x-dialog>
                        <x-dialog.open>
                            <x-form.button
                                variant="primary"
                                type="button"
                                class="border focus:border-primary-focus"
                            >
                                <x-lucide-eye class="size-4.5 sm:size-5 mr-1"/>
                                {{ __('Preview') }}
                            </x-form.button>
                        </x-dialog.open>
                        <x-dialog.panel size="xl">
                            <x-slot name="heading">
                                {{ __('Preview') }}
                            </x-slot>
                            <template x-if="@js([TemplateType::EMAIL->value, TemplateType::E_LETTER->value]).includes($wire.form.type)">
                                <x-creditor.email.preview :from="null">
                                    <x-slot name="subject">
                                        <span x-text="emailSubject"></span>
                                    </x-slot>
                                    <x-slot name="content">
                                        <span x-html="$wire.form.description"></span>
                                    </x-slot>
                                </x-creditor.email.preview>
                            </template>
                            <template x-if="@js(TemplateType::SMS->value) === $wire.form.type">
                                <x-creditor.sms.preview>
                                    <x-slot name="content">
                                        <span x-html="$wire.form.smsDescription"></span>
                                    </x-slot>
                                </x-creditor.sms.preview>
                            </template>
                            <x-slot name="footer" class="mt-3">
                                <x-dialog.close>
                                    <x-form.default-button type="button">
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
                        class="border focus:border-success-focus"
                    >
                        <x-lucide-loader-2
                            wire:target="createOrUpdate"
                            wire:loading
                            class="animate-spin size-5 mr-2"
                        />
                        {{ $form->template ? __('Update') : __('Submit') }}
                    </x-form.button>
                </div>
            </form>
        </div>
    </div>

    @script
        <script>
            Alpine.data('eLetterTypes', () => {
                return {
                    emailSubject: '',
                    description: '',
                    smsDescription: '',
                    templateType: @js(TemplateType::EMAIL->value),
                    init() {
                        this.$watch('emailSubject', () => {
                            this.$wire.form.subject = this.emailSubject
                        })
                    },
                    updateTemplateType() {
                        this.templateType = this.$el.value
                        this.$dispatch('update-description', { description: this.description })
                        this.$dispatch('update-sms-description', {smsDescription: this.$wire.form.smsDescription})
                    },
                    appendCustomFieldsIntoSubject() {
                        this.emailSubject += ' ' + this.$el.innerHTML.trim()
                    },
                    appendCustomFieldsIntoDescription() {
                        var textarea = document.querySelector('textarea')
                        var startPosition = textarea.selectionStart
                        var endPosition = textarea.selectionEnd
                        textarea.value = textarea.value.substring(0, startPosition) + this.$el.innerHTML.trim() + textarea.value.substring(endPosition, textarea.value.length)
                        textarea.selectionStart = textarea.selectionEnd = textarea.value.length
                        this.$wire.form.smdDescription = textarea.value
                        textarea.focus()
                        this.$dispatch('update-sms-description', { smsDescription: this.$wire.form.smsDescription + ' ' + this.$el.innerHTML.trim() })
                    },
                    updateDescriptionOnBackend() {
                        this.$dispatch('update-description', { description: this.description + ' ' + this.$el.innerHTML.trim() })
                    },
                }
            })
        </script>
    @endscript

    <livewire:creditor.communications.e-letter.list-view />
</div>
