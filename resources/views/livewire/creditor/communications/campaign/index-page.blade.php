@use('App\Enums\CampaignFrequency')
@use('Illuminate\Support\Carbon')
@use('App\Enums\TemplateType')
@use('Illuminate\Support\Number')

<div>
    <div class="card mb-8 px-4 py-4 sm:px-5">
        <form
            method="POST"
            autocomplete="off"
            x-data="dateRange"
            x-modelable="frequency"
            wire:model="form.frequency"
            wire:submit="createOrUpdate"
        >
            <div @class([
                'grid grid-cols-1 lg:grid-cols-2 gap-4' => !$isCreditor,
                'grid grid-cols-1 sm:grid-cols-2 gap-4' => $isCreditor,
            ])>
                <div>
                    @if ($isCreditor)
                        <x-form.select
                            wire:model="form.template_id"
                            :options="$eLettersTemplates"
                            :label="__('Eletter')"
                            name="form.template_id"
                            class="w-full"
                            :placeholder="__('template')"
                            required
                        />
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-3 items-baseline gap-4">
                            <div class="block">
                                <span class="font-semibold tracking-wide text-black lg:text-md">
                                    {{ __('Template Type') }}
                                    <span class="text-error text-base">*</span>
                                </span>
                                <div class="flex justify-between mt-3 w-full">
                                    <x-form.input-radio
                                        :label="__('Email')"
                                        wire:model="form.type"
                                        :value="TemplateType::EMAIL->value"
                                    />

                                    <x-form.input-radio
                                        :label="__('SMS')"
                                        wire:model="form.type"
                                        :value="TemplateType::SMS->value"
                                    />
                                </div>
                                @error('form.type')
                                    <div class="mt-2">
                                        <span class="text-error text-sm+">
                                            {{ $message }}
                                        </span>
                                    </div>
                                @enderror
                            </div>
                            <div class="col-span-2">
                                <template x-if="$wire.form.type === @js(TemplateType::EMAIL->value)">
                                    <x-form.select
                                        wire:model="form.template_id"
                                        :options="$emailTemplates"
                                        :label="__('Email Templates')"
                                        name="form.template_id"
                                        class="w-full"
                                        :placeholder="__('Email Template')"
                                        required
                                    />
                                </template>
                                <template x-if="$wire.form.type === @js(TemplateType::SMS->value)">
                                    <x-form.select
                                        wire:model="form.template_id"
                                        :options="$smsTemplates"
                                        :label="__('SMS Templates')"
                                        name="form.template_id"
                                        class="w-full"
                                        :placeholder="__('SMS Template')"
                                        required
                                    />
                                </template>
                            </div>
                        </div>
                    @endif
                </div>
                <div>
                    <x-form.select
                        wire:model="form.group_id"
                        :label="__('Group')"
                        :options="$groups"
                        name="form.group_id"
                        :placeholder="__('group')"
                        class="w-full"
                        required
                    />
                </div>
                <div>
                    <x-form.select
                        x-model="frequency"
                        x-on:change="changeFrequency"
                        :label="__('Frequency')"
                        :options="CampaignFrequency::displaySelectionBox()"
                        name="form.frequency"
                        :placeholder="__('frequency')"
                        class="w-full"
                        required
                    />
                </div>
                <div class="flex flex-col sm:flex-row gap-4 items-stretch sm:items-center">
                    <template x-if="! $wire.form.is_run_immediately">
                        <div
                            class="basis-1/2"
                            x-bind:class="frequency === @js(CampaignFrequency::ONCE->value) && 'basis-full'"
                        >
                            <x-form.input-field
                                wire:model="form.start_date"
                                type="date"
                                name="form.start_date"
                                :min="today()->addDay()->format('Y-m-d')"
                                :max="today()->addYear()->format('Y-m-d')"
                                x-on:change="updateEndDateLimits"
                                :label="__('Start Date')"
                                :placeholder="__('Enter Start Date')"
                                class="w-full"
                                required
                            />
                        </div>
                    </template>
                    <template x-if="frequency !== @js(CampaignFrequency::ONCE->value)">
                        <div class="basis-1/2">
                            <x-form.input-field
                                wire:model="form.end_date"
                                type="date"
                                name="form.end_date"
                                :label="__('End Date')"
                                :placeholder="__('Enter End Date')"
                                class="w-full"
                                x-bind:min="endDateMin"
                                x-bind:max="endDateMax"
                                required
                            />
                        </div>
                    </template>
                </div>
                  <template x-if="frequency === @js(CampaignFrequency::ONCE->value)">
                    <div>
                        <div class="flex flex-col lg:flex-row gap-1 mb-2">
                            <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                                {{ __('Send Now or Later?') }}<span class="text-error text-base leading-none">*</span>
                            </span>
                        </div>
                        <x-form.input-radio
                            :label="__('Run Today')"
                            wire:model.boolean="form.is_run_immediately"
                            x-on:click="$wire.form.start_date = @js(today()->toDateString())"
                            value="true"
                        />
                        <x-form.input-radio
                            :label="__('Schedule For Future Date')"
                            wire:model.boolean="form.is_run_immediately"
                            value="false"
                        />
                    </div>
                </template>
                <template x-if="frequency === @js(CampaignFrequency::WEEKLY->value)">
                    <x-form.select
                        wire:model="form.day_of_week"
                        :label="__('Weekly')"
                        :options="Carbon::getDays()"
                        name="form.day_of_week"
                        :placeholder="__('Week Day')"
                        required
                    />
                </template>
                <template x-if="frequency === @js(CampaignFrequency::MONTHLY->value)">
                    <x-form.select
                        wire:model="form.day_of_month"
                        :label="__('Monthly')"
                        :options="collect(range(1, 31))->combine(range(1, 31))->map(fn ($value) => sprintf('%02d', $value))->toArray()"
                        name="form.day_of_month"
                        :placeholder="__('Monthly Date')"
                        required
                    />
                </template>
            </div>
            <x-loader wire:loading />
            <div class="flex space-x-2 justify-center sm:justify-end mt-9">
                <a
                    wire:navigate
                    href="{{ route('home') }}"
                    class="btn border focus:border-slate-400 w-24 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                >
                    {{ __('Cancel') }}
                </a>
                <template x-if="$wire.form.is_run_immediately && frequency === @js(CampaignFrequency::ONCE->value)">
                    <x-dialog wire:model.boolean="openCampaignDialog">
                        <x-form.button
                            type="button"
                            variant="primary"
                            class="text-sm+ disabled:opacity-50"
                            wire:loading.attr="disabled"
                            wire:click="runCampaignToday"
                            x-bind:disabled="!$wire.form.template_id || !$wire.form.group_id"
                        >
                            <span>{{ __('Confirm Campaign') }}</span>
                        </x-form.button>
                        <x-dialog.panel
                            size="2xl"
                            heading="{{ __('Campaign Review') }}"
                        >
                            <div class="border">
                                <div class="flex items-center justify-between py-2 px-3 border-b">
                                    <span class="font-semibold tracking-wide text-black lg:text-md">
                                        {{ __('Date') }}
                                    </span>
                                    <span class="text-primary font-semibold text-end sm:text-left">
                                        {{ today()->format('M d, Y') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3">
                                    <span class="font-semibold tracking-wide text-black lg:text-md">
                                        {{ __('Selected Frequency') }}
                                    </span>
                                    <span class="text-primary font-semibold text-end sm:text-left">
                                        {{ __('Once') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 border-t">
                                    <span class="font-semibold tracking-wide text-black lg:text-md">
                                        {{ __('Total Consumers') }}
                                    </span>
                                    <span class="text-primary font-semibold text-end sm:text-left">
                                        {{ $groupSize }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 border-t">
                                    <span class="font-semibold tracking-wide text-black lg:text-md">
                                        {{ __('Payment Amount') }}
                                    </span>
                                    <span class="text-primary font-semibold text-end sm:text-left">
                                        {{ Number::currency((float) $groupSize * $ecoLetterPrice) }}
                                    </span>
                                </div>
                            </div>
                                <p class="mt-2">
                                    {!! __(
                                        'An amount of :amount will be deducted upon confirmation. Do you want to proceed?',
                                        ['amount' => '<b>'. Number::currency((float) $groupSize * $ecoLetterPrice) .'</b>']
                                    ) !!}
                                </p>
                            <div class="mt-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2">
                                <x-dialog.close>
                                    <x-form.default-button
                                        type="button"
                                        class="w-full sm:w-auto"
                                    >
                                        {{ __('Cancel') }}
                                    </x-form.default-button>
                                </x-dialog.close>
                                <x-form.button
                                    type="button"
                                    variant="primary"
                                    class="border focus:border-primary-focus w-full sm:w-auto disabled:opacity-50"
                                    wire:loading.attr="disabled"
                                    wire:click="createImmediately"
                                >
                                    {{ __('Create Campaign & Send Now') }}
                                </x-form.button>
                            </div>
                        </x-dialog.panel>
                    </x-dialog>
                </template>
                <template x-if="! $wire.form.is_run_immediately">
                    <x-form.button
                        type="submit"
                        variant="primary"
                        class="w-auto border focus:border-primary-focus disabled:opacity-50 text-nowrap"
                        wire:loading.attr="disabled"
                        wire:target="createOrUpdate"
                    >
                        <x-lucide-loader-2
                            wire:loading
                            wire:target="createOrUpdate"
                            class="size-5 animate-spin mr-2"
                        />
                        {{ $form->campaign_id ? __('Update') : __('Schedule Campaign') }}
                    </x-form.button>
                </template>
            </div>
        </form>
    </div>

    <livewire:creditor.communications.campaign.list-view :$isCreditor />

    @script
        <script>
            Alpine.data('dateRange', () => ({
                frequency: '',
                endDateMin: new Date(new Date().setDate(new Date().getDate() + 1)).toISOString().split('T')[0],
                endDateMax: new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0],

                changeFrequency() {
                    if (this.frequency === @js(CampaignFrequency::ONCE->value)) {
                        this.$wire.form.end_date = null
                        this.$wire.form.day_of_week = null
                        this.$wire.form.day_of_month = null
                        this.$wire.form.is_run_immediately = false
                    }
                    else if (this.frequency === @js(CampaignFrequency::WEEKLY->value)) {
                        this.$wire.form.day_of_month = null
                        this.$wire.form.is_run_immediately = false
                    }
                    else if (this.frequency === @js(CampaignFrequency::MONTHLY->value)) {
                        this.$wire.form.day_of_week = null
                        this.$wire.form.is_run_immediately = false
                    }
                    else if (this.frequency === @js(CampaignFrequency::DAILY->value)) {
                        this.$wire.form.is_run_immediately = false
                    }
                },
                updateEndDateLimits() {
                    this.$wire.form.end_date = null
                    let selectedStartDate = new Date(this.$wire.form.start_date)
                    this.endDateMin = selectedStartDate.toISOString().split('T')[0]
                    this.endDateMax = new Date(selectedStartDate.setFullYear(selectedStartDate.getFullYear() + 1))
                }
            }));
        </script>
    @endscript
</div>
