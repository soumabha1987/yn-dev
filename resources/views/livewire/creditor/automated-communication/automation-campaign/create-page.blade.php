@use('Illuminate\Support\Carbon')
@use('App\Enums\AutomationCampaignFrequency')

<div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6 w-full">
    <form method="POST" wire:submit="create" autocomplete="off">
        <div
            x-data="{ frequency: '' }"
            x-modelable="frequency"
            wire:model="form.frequency"
            class="card py-4 px-4 sm:px-5"
        >
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-form.select
                        :label="__('Communication Status')"
                        :options="$communicationStatusCodes"
                        name="form.communication_status_id"
                        wire:model="form.communication_status_id"
                        required
                    />
                </div>
                <div>
                    <x-form.select
                        x-model="frequency"
                        :label="__('Frequency')"
                        :options="AutomationCampaignFrequency::displaySelectionBox()"
                        name="form.frequency"
                        required
                    />
                </div>
                <div>
                    <x-form.select
                        wire:model="form.hours"
                        :label="__('Select hours in Eastern Standard Time (EST) Timezone')"
                        :options="collect(range(0, 23))->combine(range(0, 23))->map(fn ($value) => sprintf('%02d', $value))->toArray()"
                        name="form.hours"
                        required
                    />
                </div>
                <div x-show="frequency === '{{ AutomationCampaignFrequency::WEEKLY->value }}'">
                    <x-form.select
                        wire:model="form.weekly"
                        :label="__('Select Weekly')"
                        :options="Carbon::getDays()"
                        name="form.weekly"
                        x-bind:required="frequency === '{{ AutomationCampaignFrequency::WEEKLY->value }}' ? true : false"
                    />
                </div>
                <div x-show="frequency === '{{ AutomationCampaignFrequency::HOURLY->value }}'">
                    <x-form.select
                        wire:model="form.hourly"
                        :label="__('Select Hourly Period')"
                        :options="[12 => __('12 hour'), 36 => __('36 hour'), 48 => __('48 hour'), 72 => __('72 hour')]"
                        name="form.hourly"
                        x-bind:required="frequency === '{{ AutomationCampaignFrequency::HOURLY->value }}' ? true : false"
                    />
                </div>
            </div>
            <div class="flex justify-center sm:justify-end space-x-2 mt-9">
                <a
                    wire:navigate
                    href="{{ route('super-admin.automation-campaigns') }}"
                    class="btn bg-slate-150 font-medium border focus:border-slate-400 text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 min-w-[7rem]"
                >
                    {{ __('Cancel') }}
                </a>
                <x-form.button
                    type="submit"
                    variant="primary"
                    wire:target="create"
                    wire:loading.attr="disabled"
                    class="border focus:border-primary-focus font-medium min-w-[7rem] disabled:opacity-50"
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
    </form>
</div>
