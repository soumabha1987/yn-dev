@use('App\Enums\AutomationCampaignFrequency')
@use('Illuminate\Support\Carbon')

<div>
    <form method="POST" wire:submit="update" autocomplete="off">
        @csrf
        <div
            x-data="{ frequency: @entangle('form.frequency') }"
            x-init="$watch('frequency', (newFrequency) => {
                if (newFrequency !== '{{ AutomationCampaignFrequency::WEEKLY->value }}') {
                    $wire.form.weekly = ''
                }
                if (newFrequency !== '{{ AutomationCampaignFrequency::HOURLY->value }}') {
                    $wire.form.start_at = ''
                    $wire.form.hourly = ''
                }
            })"
            class="card py-4 px-4 sm:px-5"
        >
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-form.select
                        wire:model="form.communication_status_id"
                        :label="__('Communication Status')"
                        :options="$communicationStatusCodes"
                        name="form.communication_status_id"
                    />
                </div>
                <div>
                    <x-form.select
                        x-model="frequency"
                        wire:model="form.frequency"
                        :label="__('Frequency')"
                        :options="AutomationCampaignFrequency::displaySelectionBox()"
                        name="form.frequency"
                    />
                </div>
                <div>
                    <x-form.select
                        :label="__('Select hours in Eastern Standard Time (EST) Timezone')"
                        :placeholder="__('Time hours')"
                        :options="collect(range(0, 23))->combine(range(0, 23))->map(fn($value) => sprintf('%02d', $value))->toArray()"
                        name="form.hours"
                        wire:model="form.hours"
                    />
                </div>
                <div x-show="frequency === '{{ AutomationCampaignFrequency::WEEKLY->value }}'">
                    <x-form.select
                        wire:model="form.weekly"
                        :label="__('Select Weekly')"
                        :placeholder="__('WeekDay')"
                        :options="Carbon::getDays()"
                        name="form.weekly"
                    />
                </div>
                <div x-show="frequency === '{{ AutomationCampaignFrequency::HOURLY->value }}'">
                    <x-form.select
                        wire:model="form.hourly"
                        :label="__('Select Hourly Period')"
                        :placeholder="__('Horus')"
                        :options="[12 => __('12 hour'), 36 => __('36 hour'), 48 => __('48 hour'), 72 => __('72 hour')]"
                        name="form.hourly"
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
                <div wire:dirty wire:target="form.frequency">
                    <x-dialog>
                        <x-dialog.open>
                            <x-form.button 
                                type="button" 
                                variant="primary" 
                                class="font-medium min-w-[7rem]"
                            >
                                {{ __('Submit') }}
                            </x-form.button>
                        </x-dialog.open>

                        <x-dialog.panel size="lg">
                            <x-slot name="heading">
                                {{ __('Important Notice') }}
                            </x-slot>
                            <b>{{ __('Updating the frequency of this campaign, please be aware that the change will take effect immediately, without considering of the previous frequency.') }}</b>
                            <x-slot name="footer" class="mt-4">
                                <x-dialog.close>
                                    <x-form.default-button 
                                        type="button" 
                                        class="min-w-[7rem]"
                                    >
                                        {{ __('Close') }}
                                    </x-form.default-button>
                                </x-dialog.close>
                                <x-form.button 
                                    wire:click="update" 
                                    type="submit" 
                                    variant="primary"
                                    class="font-medium min-w-[7rem]"
                                >
                                    {{ __('Proceed') }}
                                </x-form.button>
                            </x-slot>
                        </x-dialog.panel>
                    </x-dialog>
                </div>
                <div wire:dirty.remove wire:target="form.frequency">
                    <x-form.button 
                        type="submit" 
                        variant="primary"
                        wire:target="update"
                        wire:loading.attr="disabled"
                        class="border focus:border-primary-focus font-medium min-w-[7rem] disabled:opacity-50"
                    >
                        <x-lucide-loader-2
                            wire:target="update"
                            wire:loading
                            class="animate-spin size-5 mr-2"
                        />
                        {{ __('Update') }}
                    </x-form.button>
                </div>
            </div>
        </div>
    </form>
</div>
