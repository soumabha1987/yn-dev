@use('App\Enums\MembershipFrequency')
@use('App\Enums\MembershipFeatures')

<div>
    @if ($createPlan)
        <x-dialog>
            <x-dialog.open>
                <x-form.button
                    :variant="$hasSpecialMembership ? 'info' : 'primary'"
                    type="button"
                    class="text-xs sm:text-sm text-nowrap px-2 sm:px-3 py-1.5"
                >
                    <div class="flex space-x-1 items-center">
                        @if ($hasSpecialMembership)
                            <x-lucide-refresh-cw class="size-4.5 sm:size-5 text-white" />
                            <span>{{ __('Update Plan') }}</span>
                        @else
                            <x-lucide-circle-plus class="size-4.5 sm:size-5 text-white" />
                            <span>{{ __('Create Plan') }}</span>
                        @endif

                    </div>
                </x-form.button>
            </x-dialog.open>
            <x-dialog.panel
                size="5xl"
                heading="{{ $hasSpecialMembership ? __('Update Special Membership') : __('Create Special Membership') }}"
                x-on:close-dialog-box.window="() => {
                    dialogOpen = false
                    $dispatch('refresh-parent')
                }"
            >
                <div>
                    <form
                        wire:submit="createOrUpdate"
                        autocomplete="off"
                    >
                        <div class="m-3">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                <div>
                                    <x-form.input-field
                                        type="text"
                                        :label="__('Name')"
                                        wire:model="form.name"
                                        name="form.name"
                                        class="w-full"
                                        maxlength="40"
                                        :placeholder="__('Enter Name')"
                                        required
                                    />
                                </div>
                                <div>
                                    <x-form.input-group
                                        type="text"
                                        wire:model="form.price"
                                        :label="__('Licensing Fee')"
                                        name="form.price"
                                        :placeholder="__('Licensing Fee')"
                                        icon="$"
                                        required
                                    />
                                </div>
                                <div>
                                    <x-form.input-group
                                        type="text"
                                        wire:model="form.fee"
                                        :label="__('Percentage of Payments (%)')"
                                        name="form.fee"
                                        :placeholder="__('Percentage of Payments(Fees on all consumer payments)')"
                                        icon="%"
                                        icon-position="right"
                                        required
                                    />
                                </div>
                                <div x-data="accountScope">
                                    <x-form.input-field
                                        :label="__('Upload Accounts Limit')"
                                        x-model="accountInScope"
                                        x-on:input="formatWithCommas"
                                        type="text"
                                        name="form.upload_accounts_limit"
                                        class="w-full"
                                        :placeholder="__('Upload Accounts Limit')"
                                        min="1"
                                        required
                                    />
                                </div>
                                <div>
                                    <x-form.select
                                        :label="__('Frequency')"
                                        :placeholder="__('Frequency')"
                                        :options="MembershipFrequency::displaySelectionBox()"
                                        name="form.frequency"
                                        wire:model="form.frequency"
                                        required
                                    />
                                </div>
                                <div>
                                    <x-form.input-group
                                        :label="__('E-Letter Fee')"
                                        type="text"
                                        name="form.e_letter_fee"
                                        :placeholder="__('E-Letter Fee')"
                                        wire:model="form.e_letter_fee"
                                        icon="$"
                                        required
                                    />
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-5">
                                <div class="my-2">
                                    <x-form.text-area
                                        :label="__('Description')"
                                        wire:model="form.description"
                                        name="form.description"
                                        class="w-full"
                                        rows="5"
                                        required
                                    />
                                </div>
                                <div class="my-2 col-span-2">
                                <span class="font-medium tracking-wide text-black lg:text-sm+">
                                    {{__('Features')}}
                                </span>
                                    @error('form.features')
                                    <div class="mt-2">
                                        <span class="text-error text-sm+">
                                            {{ $message }}
                                        </span>
                                    </div>
                                    @enderror
                                    <div class="grid grid-cols-1 sm:grid-cols-2">
                                        @foreach (MembershipFeatures::displayFeatures() as $key => $value)
                                            <div class="m-2 text-black font-medium">
                                                <x-form.switch
                                                    wire:model="form.features"
                                                    name="form.features.{{ $key }}"
                                                    value="{{ $key }}"
                                                    label="{{ $value }}"
                                                />
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-x-2 text-right">
                            <div class="flex gap-x-2 items-center justify-end">
                                <x-dialog.close>
                                    <x-form.default-button type="button">
                                        {{ __('Close') }}
                                    </x-form.default-button>
                                </x-dialog.close>
                                <button
                                    type="submit"
                                    wire:target="createOrUpdate"
                                    wire:loading.attr="disabled"
                                    class="btn border focus:border-primary-focus select-none disabled:opacity-50 text-white bg-primary hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                                >
                                    <x-lucide-loader-2
                                        wire:target="createOrUpdate"
                                        wire:loading
                                        class="animate-spin size-5 mr-2"
                                    />
                                    {{ $hasSpecialMembership ? __('Update') : __('Create') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </x-dialog.panel>
        </x-dialog>
    @else
        <x-dialog>
            <x-dialog.open>
                <x-form.button
                    class="text-xs sm:text-sm px-2 sm:px-3 py-1.5"
                    type="button"
                    variant="success"
                >
                    <div class="flex space-x-1 items-center">
                        <x-heroicon-o-eye class="size-4.5 sm:size-5 text-white" />
                        <span>{{ __('View') }}</span>
                    </div>
                </x-form.button>
            </x-dialog.open>

            <x-dialog.panel
                size="5xl"
                :heading="str($membershipInquiry->company->company_name)->title()"
                x-on:close-dialog-box.window="() => {
                    dialogOpen = false
                    $dispatch('refresh-parent')
                }"
            >
                <div>
                    <h4 class="flex items-center cursor-pointer justify-between text-base font-medium text-slate-800 bg-slate-50 px-4 py-3 rounded-lg border border-slate-200">
                        <span>{{ __('Company Details') }}</span>
                    </h4>
                    <div class="max-w-5xl">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 mt-2 sm:gap-5">
                            <div class="rounded-lg p-4 border border-slate-200">
                                <div class="flex justify-between space-x-1 space-x-reverse">
                                    <div
                                        class="text-sm font-semibold text-slate-700 overflow-x-auto is-scrollbar-hidden min-w-full">
                                        {{ str($membershipInquiry->company->company_name)->title() }}
                                    </div>
                                </div>
                                <p class="mt-1 text-xs+">{{ __('Company Name') }}</p>
                            </div>
                            <div class="rounded-lg p-4 border border-slate-200">
                                <div class="flex justify-between space-x-1 space-x-reverse">
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ $membershipInquiry->company->owner_email }}
                                    </p>
                                </div>
                                <p class="mt-1 text-xs+">{{ __('Email') }}</p>
                            </div>
                            <div class="rounded-lg p-4 border border-slate-200">
                                <div class="flex justify-between space-x-1 space-x-reverse">
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ $membershipInquiry->company->owner_phone }}
                                    </p>
                                </div>
                                <p class="mt-1 text-xs+">{{ __('Contact Number') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="border border-slate-200 bg-slate-50 px-4 py-3 mt-2 rounded-lg">
                        @if ($membershipInquiry->description)
                            <h4 class="flex items-center justify-between text-base font-medium text-slate-800">
                                <span>{{ __('Additional Notes') }}</span>
                            </h4>
                            <p class="mt-2">{!! $membershipInquiry->description !!}</p>
                        @endif
                    </div>
                </div>
            </x-dialog.panel>
        </x-dialog>
    @endif

    @script
        <script>
            Alpine.data('accountScope', () => ({
                accountInScope: '',
                init() {
                    if (this.$wire.form.upload_accounts_limit) {
                        this.accountInScope = this.$wire.form.upload_accounts_limit.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                    }
                },
                formatWithCommas() {
                    let accountInScope = this.$el.value.replace(/[^0-9]/g, '')
                    this.accountInScope = accountInScope.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                    this.$wire.form.upload_accounts_limit = accountInScope
                }
            }))
        </script>
    @endscript
</div>
