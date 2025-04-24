<x-consumer.profile-layout route-name="communication-controls">
    <div
        class="card"
        x-data="communicationControls"
    >
        <div @class([
            'items-center space-y-4 border-slate-200 p-4 sm:flex-row sm:justify-between sm:space-y-0 sm:px-5',
            'border-b' => $form->text_permission || $form->email_permission,
        ])>
            <h2 class="text-lg font-semibold tracking-wide text-black">
                {{ __('Communication Preferences') }}
            </h2>
            <span class="text-black-50">{{ __('Manage your communication preferences to fit your lifestyle') }}</span>
        </div>
        @if (! $form->text_permission && ! $form->email_permission)
            <div class="alert flex overflow-hidden bg-warning/10 text-warning">
                <div class="flex flex-1 items-center space-x-3 p-4">
                    <x-lucide-triangle-alert class="size-5" />
                    <div class="flex-1">
                        {{ __('Email and text permissions are disabled. We will use email for communication purposes.') }}
                    </div>
                </div>
            </div>
        @endif

        <div class="flex justify-between px-4 sm:px-5 mt-4">
            <span class="font-semibold select-none tracking-wide text-black line-clamp-1 lg:text-base">
                {{ __('Text Communication') }}
            </span>
            <div class="flex items-center gap-x-2">
                <div
                    x-data="{ displayTextPermissionMessage: false }"
                    x-on:update-text-permission.window="displayTextPermissionMessage = true"
                >
                    <span
                        x-show="displayTextPermissionMessage"
                        x-effect="if (displayTextPermissionMessage) setTimeout(() => displayTextPermissionMessage = false, 2500)"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-90"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-90"
                        class="text-success"
                    >
                        {{ __('Updated!') }}
                    </span>
                </div>
                <input
                    wire:model.boolean="form.text_permission"
                    wire:click="updateTextPermission"
                    class="form-switch h-5 w-10 rounded-full bg-slate-300 before:rounded-full before:bg-slate-50 checked:bg-primary checked:before:bg-white"
                    type="checkbox"
                >
            </div>
        </div>
        <span class="px-5 mt-2 text-black-50 max-w-xl">{{ __('By enabling, you authorize receiving communications via text on the mobile phone number below.') }}</span>
        <div class="mt-2 px-4 sm:px-5 max-w-lg">
            <label class="block">
                <span class="relative mt-1.5 flex">
                     <input
                        x-model="$wire.form.mobile"
                        x-mask:dynamic="'(999) 999-9999'"
                        @class([
                            'form-input peer w-full rounded border bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 focus:border-primary',
                            'border-error' => $errors->has('form.mobile'),
                            'border-slate-300' => $errors->missing('form.mobile'),
                        ])
                        placeholder="(403) 123-4789"
                        x-on:keydown.enter="updateMobile"
                        autocomplete="off"
                    >
                    <button
                        x-on:click="updateMobile"
                        :disabled="!isMobileChanged()"
                        :class="!isMobileChanged() && 'opacity-50'"
                        class="btn mx-3 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                    >
                        {{ __('Update') }}
                    </button>
                    <span @class([
                        'pointer-events-none absolute flex h-full w-10 items-center justify-center peer-focus:text-primary',
                        'text-error' => $errors->has('form.mobile'),
                        'text-slate-400' => $errors->missing('form.mobile'),
                    ])>
                        <x-lucide-smartphone class="size-5" />
                    </span>
                </span>
                @error('form.mobile')
                    <div class="mt-1">
                        <span class="text-error">
                            {{ $message }}
                        </span>
                    </div>
                @enderror
            </label>
        </div>

        <hr class="my-4 h-px bg-slate-200">

        <div class="flex justify-between px-4 sm:px-5 mt-4">
            <span class="font-semibold select-none tracking-wide text-black line-clamp-1 lg:text-base">
                {{ __('Email Communication') }}
            </span>
            <div class="flex items-center gap-x-2">
                <div
                    x-data="{ displayEmailPermissionMessage: false }"
                    x-on:update-email-permission.window="displayEmailPermissionMessage = true"
                >
                    <span
                        x-show="displayEmailPermissionMessage"
                        x-effect="if (displayEmailPermissionMessage) setTimeout(() => displayEmailPermissionMessage = false, 2500)"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-90"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-90"
                        class="text-success"
                    >
                        {{ __('Updated!') }}
                    </span>
                </div>
                <input
                    wire:model.boolean="form.email_permission"
                    wire:click="updateEmailPermission"
                    class="form-switch h-5 w-10 rounded-full bg-slate-300 before:rounded-full before:bg-slate-50 checked:bg-primary checked:before:bg-white"
                    type="checkbox"
                >
            </div>
        </div>
        <span class="px-5 mt-2 text-black-50 max-w-xl">{{ __('By enabling, you authorize receiving communications via the email below.') }}</span>
        <div class="mt-2 mb-4 px-4 sm:px-5 max-w-lg">
            <label class="block">
                <span class="relative mt-1.5 flex">
                     <input
                        wire:model="form.email"
                        @class([
                            'form-input peer w-full rounded border bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 focus:border-primary',
                            'border-error' => $errors->has('form.email'),
                            'border-slate-300' => $errors->missing('form.email'),
                        ])
                        placeholder="dweaver@gmail.com"
                        x-on:keydown.enter="updateEmail"
                        autocomplete="off"
                    >
                    <button
                        x-on:click="updateEmail"
                        :disabled="!isEmailChanged()"
                        :class="!isEmailChanged() && 'opacity-50'"
                        class="btn mx-3 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                    >
                        {{ __('Update') }}
                    </button>
                    <span @class([
                        'pointer-events-none absolute flex h-full w-10 items-center justify-center peer-focus:text-primary',
                        'text-error' => $errors->has('form.email'),
                        'text-slate-400' => $errors->missing('form.email'),
                    ])>
                        <x-lucide-mail class="size-5" />
                    </span>
                </span>
                @error('form.email')
                    <div class="mt-1">
                        <span class="text-error">
                            {{ $message }}
                        </span>
                    </div>
                @enderror
            </label>
        </div>

        <div x-show="@js($isCommunicationUpdated)">
            <hr class="my-4 h-px bg-slate-200">

            <div class="flex flex-col justify-start mb-4 px-4 sm:px-5 mt-4">
                <h3 class="font-semibold select-none tracking-wide text-black line-clamp-1 lg:text-base">
                    {{ __('Confirm Contact Details') }}
                </h3>

                <div class="flex justify-between items-center mt-2">
                    <span class="text-black-50">
                        {{ __('Please review the contact details below and ensure they are correct before proceeding.') }}
                    </span>

                    <x-form.button
                        variant="primary"
                        type="button"
                        wire:click="confirmCommunicationSettings"
                    >
                        {{ __('Confirm') }}
                    </x-form.button>
                </div>
            </div>
        </div>
    </div>

    @script
        <script>
            Alpine.data('communicationControls', () => ({
                originalMobile: '',
                originalEmail: '',
                init() {
                    this.$nextTick(() => {
                        this.originalMobile = this.$wire.form.mobile || ''
                        this.originalEmail  = this.$wire.form.email  || ''
                    })
                },
                isMobileChanged() {
                    return this.$wire.form.mobile !== this.originalMobile
                },
                isEmailChanged() {
                    return this.$wire.form.email !== this.originalEmail
                },
                async updateMobile() {
                    if (!this.isMobileChanged()) return
                    await this.$wire.updateMobile()
                    this.originalMobile = this.$wire.form.mobile
                },
                async updateEmail() {
                    if (!this.isEmailChanged()) return
                    await this.$wire.updateEmail()
                    this.originalEmail = this.$wire.form.email
                }
            }))
        </script>
    @endscript
</x-consumer.profile-layout>
