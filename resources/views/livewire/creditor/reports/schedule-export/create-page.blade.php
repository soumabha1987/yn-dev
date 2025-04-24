@use('App\Enums\ScheduleExportFrequency')
@use('App\Enums\ScheduleExportDeliveryType')
@use('Illuminate\Support\Carbon')
@use('App\Enums\Role')

<div>
    <div class="card px-4 py-4 sm:px-5">
        <form
            x-data="watchDeliveryType"
            wire:submit="create"
            method="POST"
            autocomplete="off"
        >
            @if (filled($errors->all()))
                <div class="alert flex overflow-hidden rounded-lg bg-error/10 text-error">
                    <ul class="text-sm+">
                        @foreach ($errors->all() as $message)
                            <li class="p-4">{!! $message !!}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 items-end gap-x-5">
                <div class="my-2">
                    <x-form.select
                        wire:model="form.report_type"
                        :label="__('Report Name')"
                        :options="$reportTypes"
                        name="form.report_type"
                        :placeholder="__('Report Type')"
                        required
                    />
                </div>
                @if (auth()->user()->hasRole(Role::SUPERADMIN) && count($clients) > 0)
                    <div class="my-2">
                        <x-form.select
                            wire:model="form.company_id"
                            :label="__('Select Client')"
                            :options="$clients"
                            name="form.company_id"
                        />
                    </div>
                @endif
                @if (auth()->user()->hasRole(Role::CREDITOR))
                    <div class="my-2">
                        <x-form.select
                            wire:model="form.subclient_id"
                            :label="__('Accounts')"
                            :options="$clients"
                            name="form.subclient_id"
                        >
                            <x-slot name="blankOption">
                                <option value="">{{ __('Master - All') }}</option>
                            </x-slot>
                        </x-form.select>
                    </div>
                @endif
                <div class="my-2">
                    <x-form.select
                        wire:model="form.frequency"
                        :label="__('Frequency')"
                        :options="ScheduleExportFrequency::displaySelectionBox()"
                        name="form.frequency"
                        :placeholder="__('Frequency')"
                        required
                    />
                </div>
                <div class="my-2">
                    <label class="block font-semibold tracking-wide text-black lg:text-md">
                        {{ __("Delivery Type") }}<span class="text-error text-base">*</span>
                    </label>
                    <div class="flex items-center space-x-2 mt-3">
                        @hasrole (Role::CREDITOR)
                            <x-form.input-radio
                                wire:model="form.delivery_type"
                                :label="__('SFTP')"
                                name="form.delivery_type"
                                :value="ScheduleExportDeliveryType::SFTP->value"
                            />
                        @endhasrole
                        <x-form.input-radio
                            wire:model="form.delivery_type"
                            :label="__('Email')"
                            name="form.delivery_type"
                            :value="ScheduleExportDeliveryType::EMAIL->value"
                        />
                    </div>
                </div>
                @hasrole (Role::CREDITOR)
                    @if (count($mappedHeaders) > 0)
                        <div class="my-2">
                            <x-form.select
                                wire:model="form.csv_header_id"
                                name="form.csv_header_id"
                                :options="$mappedHeaders"
                                :placeholder="__('header profile')"
                                :label="__('Header Profile')"
                                class="w-full"
                            />
                        </div>
                    @endif
                    <template x-if="$wire.form.delivery_type === @js(ScheduleExportDeliveryType::SFTP->value)">
                        <div class="my-2">
                            <x-form.select
                                wire:model="form.sftp_connection_id"
                                :label="__('SFTP Connections')"
                                :placeholder="__('Connections')"
                                :options="$sftpConnections"
                                name="form.sftp_connection_id"
                                required
                            />
                            @if (blank($sftpConnections))
                                <div class="mt-2">
                                    <a
                                        wire:navigate
                                        href="{{ route('creditor.sftp.create') }}"
                                        class="flex items-center space-x-2 text-red-500"
                                    >
                                        <x-lucide-circle-arrow-out-up-right class="size-5" />
                                        <span>{{ __('Create SFTP Connection') }}</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </template>
                @endhasrole
                <template x-if="$wire.form.delivery_type === @js(ScheduleExportDeliveryType::EMAIL->value)">
                    <div class="my-2">
                        <x-form.input-field
                            type="text"
                            wire:model="form.emails"
                            :label="__('Email')"
                            name="form.emails"
                            :placeholder="__('Enter Emails')"
                            class="w-full"
                            required
                        >
                            <x-slot name="instruction">
                                <span class="text-xs lg:text-sm">{{ __('(Separate multiple emails with a comma)') }}</span>
                            </x-slot>
                        </x-form.input-field>
                    </div>
                </template>
            </div>
            <div class="my-2 flex justify-center sm:justify-end space-x-2 mt-9">
                <a
                    wire:navigate
                    href="{{ auth()->user()->hasRole(Role::SUPERADMIN) ? route('schedule-export') : route('creditor.schedule-export') }}"
                    class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                >
                    {{ __('Cancel') }}
                </a>
                <x-form.button
                    type="submit"
                    variant="primary"
                    wire:target="create"
                    wire:loading.attr="disabled"
                    class="disabled:opacity-50 border focus:border-primary-focus"
                >
                    <x-lucide-loader-2
                        wire:loading
                        wire:target="create"
                        class="size-5 animate-spin mr-2"
                    />
                    {{ __('Add to Schedule') }}
                </x-form.button>
            </div>
        </form>
    </div>

    @script
        <script>
            Alpine.data('watchDeliveryType', () => ({
                init() {
                    this.$wire.$watch('form.delivery_type', () => {
                        if (this.$wire.form.delivery_type === @js(ScheduleExportDeliveryType::EMAIL->value)) {
                            this.$wire.form.sftp_connection_id = ''
                            this.$wire.form.csv_header_id = ''
                        } else {
                            this.$wire.form.emails = ''
                        }
                    })
                },
            }))

            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 504) {
                        $notification({ text: '{{ __('This credentials is not working') }}', variant: 'error' })

                        preventDefault()
                    }
                })
            })
        </script>
    @endscript
</div>
