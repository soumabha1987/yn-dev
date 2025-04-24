@use('App\Enums\ReportType')
@use('App\Enums\Role')

<div>
    <form
        wire:submit="generateReport"
        method="POST"
        autocomplete="off"
    >
        <div
            x-data="{ reportType: '' }"
            x-modelable="reportType"
            wire:model="form.report_type"
            class="card p-4 w-full lg:w-1/2"
        >
            <div class="grid grid-cols-1 items-baseline sm:grid-cols-2 gap-3 my-3">
                <div>
                    <x-form.select
                        x-model="reportType"
                        :label="__('Report Type')"
                        :options="$reportTypes"
                        name="form.report_type"
                        required
                    />
                </div>
                @role(Role::SUPERADMIN->value)
                    <div>
                        <x-form.select
                            wire:model="form.company_id"
                            :label="__('Select Company')"
                            :options="$subAccounts"
                            name="form.company_id"
                        />
                    </div>
                @endrole
                @role(Role::CREDITOR)
                    <div>
                        <x-form.select
                            wire:model="form.subclient_id"
                            :label="__('Accounts In Scope')"
                            :options="$subAccounts"
                            name="form.subclient_id"
                            required
                        />
                    </div>
                @endrole
            </div>
            <div
                x-data="dateRange"
                class="grid grid-cols-1 sm:grid-cols-2 gap-3 my-3"
            >
                {{-- Use flatpicker instead of native datepicker --}}
                <div>
                    <x-form.input-field
                        wire:model="form.start_date"
                        type="date"
                        :label="__('Start Date')"
                        class="w-full"
                        ::max="reportType !== '{{ ReportType::SCHEDULED_TRANSACTIONS }}' ? '{{ now()->toDateString() }}' : ''"
                        ::min="reportType === '{{ ReportType::SCHEDULED_TRANSACTIONS }}' ? '{{ now()->toDateString() }}' : ''"
                        ::change="onFromDateChange"
                        name="form.start_date"
                        required
                    />
                </div>
                <div>
                    <x-form.input-field
                        wire:model="form.end_date"
                        type="date"
                        :label="__('End Date')"
                        name="form.end_date"
                        required
                        class="w-full"
                        ::min="endDateMin"
                        ::max="endDateMax"
                    />
                </div>
            </div>
            <div class="flex mt-6 sm:justify-end justify-center">
                <x-form.button
                    type="submit"
                    variant="primary"
                    class="flex space-x-2 items-center disabled:opacity-50"
                    wire:loading.attr="disabled"
                >
                    <x-lucide-hammer wire:loading.remove class="size-5" />
                    <x-lucide-loader-2
                        wire:loading
                        wire:target="generateReport"
                        class="size-5 animate-spin"
                    />
                    <span>{{ __('Generate Report') }}</span>
                </x-form.button>
            </div>
        </div>
    </form>

    @script
        <script>
            Alpine.data('dateRange', () => ({
                endDateMin: '',
                endDateMax: new Date().toISOString().split('T')[0],
                onFromDateChange() {
                    const startDate = new Date($wire.form.start_date)
                    const maxToDate = new Date(startDate)
                    maxToDate.setMonth(maxToDate.getMonth() + 2)

                    const today = new Date()
                    this.endDateMax = today.toISOString().split('T')[0]

                    if ($wire.form.report_type === '{{ ReportType::SCHEDULED_TRANSACTIONS }}') {
                        this.endDateMax = maxToDate.toISOString().split('T')[0]
                    } else {
                        this.endDateMax = maxToDate > today ? today.toISOString().split('T')[0] : maxToDate.toISOString().split('T')[0]
                    }

                    this.endDateMin = $wire.form.start_date

                    const endDate = new Date($wire.form.end_date)

                    if (endDate > maxToDate || endDate < startDate) {
                        $wire.form.end_date = ''
                    }
                }
            }));
        </script>
    @endscript
</div>
