<div class="card px-4 py-4 sm:px-5">
    <form
        method="POST"
        autocomplete="off"
        wire:submit="create"
    >
        <div class="grid grid-cols-1 sm:grid-cols-3 items-end gap-4">
            <div>
                <x-form.input-field
                    type="text"
                    wire:model="form.name"
                    name="form.name"
                    :label="__('Company Name')"
                    :placeholder="__('Enter Company Name')"
                    class="w-full"
                    required
                />
            </div>
            <div>
                <x-form.input-field
                    type="text"
                    wire:model="form.contact_first_name"
                    name="form.contact_first_name"
                    :label="__('Contact First Name')"
                    :placeholder="__('Enter Contact First Name')"
                    class="w-full"
                    required
                />
            </div>
            <div>
                <x-form.input-field
                    type="text"
                    wire:model="form.contact_last_name"
                    name="form.contact_last_name"
                    :label="__('Contact Last Name')"
                    :placeholder="__('Enter Contact Last Name')"
                    class="w-full"
                    required
                />
            </div>
            <div>
                <x-form.input-field
                    type="email"
                    wire:model="form.contact_email"
                    name="form.contact_email"
                    :label="__('Contact Email')"
                    :placeholder="__('Enter Contact Email')"
                    class="w-full"
                    required
                />
            </div>
            <div>
                <x-form.us-phone-number
                    :label="__('Contact Phone')"
                    name="form.contact_phone"
                    :placeholder="__('Enter Contact Phone')"
                    required
                />
            </div>
            <div>
                <x-form.input-group
                    :label="__('Revenue Share %')"
                    type="text"
                    wire:model="form.revenue_share"
                    name="form.revenue_share"
                    :placeholder="__('Enter Revenue Share %')"
                    icon="%"
                    icon-position="right"
                    required
                />
            </div>
            <div x-data="creditors_quota">
                <x-form.input-field
                    type="text"
                    name="form.creditors_quota"
                    x-on:input="formatWithCommas"
                    :label="__('Creditors in Scope')"
                    :placeholder="__('Creditors in Scope')"
                    class="w-full"
                    required
                />
            </div>
            <div class="sm:col-span-2">
                <x-form.input-field
                    :label="__('Report Email')"
                    type="text"
                    wire:model="form.report_emails"
                    name="form.report_emails"
                    :placeholder="__('Enter Report Emails')"
                    class="w-full"
                    required
                >
                    <x-slot name="instruction">
                        <span class="text-xs lg:text-sm">{{ __('(make sure to include YN contact, multiple email with comma separated)') }}</span>
                    </x-slot>
                </x-form.input-field>
            </div>
        </div>
        <div class="flex space-x-2 justify-end mt-9">
            <a
                wire:navigate
                href="{{ route('super-admin.manage-partners') }}"
                class="btn border focus:border-slate-400 w-24 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
            >
                {{ __('Cancel') }}
            </a>
            <x-form.button
                type="submit"
                variant="primary"
                class="border focus:border-primary-focus w-24 disabled:opacity-50"
                wire:target="create"
                wire:loading.attr="disabled"
            >
                <x-lucide-loader-2
                    wire:target="create"
                    wire:loading
                    class="animate-spin size-5 mr-2"
                />
                {{ __('Save') }}
            </x-form.button>
        </div>
    </form>

    @script
        <script>
            Alpine.data('creditors_quota', () => ({
                formatWithCommas() {
                    this.$event.target.value = this.$event.target.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                    this.$wire.form.creditors_quota = this.$event.target.value.replace(/[,\s]/g, '')
                },
            }))
        </script>
    @endscript
</div>
