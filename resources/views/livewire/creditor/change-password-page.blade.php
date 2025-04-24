@use('App\Enums\Role')
@use('App\Enums\CreditorCurrentStep')

<div>
    <div
        @class([
            'sm:max-w-xl',
            'my-10 mx-auto' => auth()->user()->hasRole(Role::CREDITOR) && auth()->user()->company->current_step !== CreditorCurrentStep::COMPLETED->value
        ])
    >
        <form
            method="POST"
            wire:submit="updatePassword"
            autocomplete="off"
        >
            <div class="card px-4 py-4 sm:px-5">
                <div>
                    <div class="my-1.5 flex items-center justify-between">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Current Password') }}<span class="text-error text-base">*</span>
                        </span>
                    </div>
                    <x-form.input-password
                        name="form.currentPassword"
                        wire:model="form.currentPassword"
                        placeholder="{{ __('Enter Current Password') }}"
                    />
                </div>

                <div class="mt-2">
                    <div class="my-1.5 flex items-center justify-between">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('New Password') }}<span class="text-error text-base">*</span>
                        </span>
                    </div>
                    <x-auth.password
                        wire:model="form.newPassword"
                        name="form.newPassword"
                        :hasIcon="false"
                        label-class="!mt-2"
                        class="border border-slate-300 bg-transparent px-3 py-2 pr-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary"
                    />
                </div>
                <div class="mt-2">
                    <div class="my-1.5 flex items-center justify-between">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Confirm Password') }}<span class="text-error text-base">*</span>
                        </span>
                    </div>
                    <x-form.input-password
                        name="form.newPassword_confirmation"
                        wire:model="form.newPassword_confirmation"
                        placeholder="{{ __('Enter Confirm Password') }}"
                    />
                </div>
                <div class="flex justify-center sm:justify-end space-x-2 mt-9">
                    <a
                        wire:navigate
                        href="{{ route('home') }}"
                        class="btn border focus:border-slate-400 font-medium text-slate-800 bg-slate-150 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 min-w-[7rem]"
                    >
                        {{ __('Cancel') }}
                    </a>
                    <x-form.button
                        type="submit"
                        variant="primary"
                        wire:target="updatePassword"
                        wire:loading.attr="disabled"
                        class="border focus:border-primary-focus font-medium min-w-[7rem] disabled:opacity-50"
                    >
                        <x-lucide-loader-2
                            wire:loading
                            wire:target="updatePassword"
                            class="size-5 animate-spin mr-2"
                        />
                        {{ __('Change Password') }}
                    </x-form.button>
                </div>
            </div>
        </form>
    </div>
</div>
