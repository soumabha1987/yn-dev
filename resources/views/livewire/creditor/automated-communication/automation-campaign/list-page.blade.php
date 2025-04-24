@use('App\Enums\AutomatedTemplateType')
@use('App\Enums\AutomationCampaignFrequency')

<div>
    <div class="card">
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $automationCampaigns->isNotEmpty(),
                'justify-end' => $automationCampaigns->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$automationCampaigns" />
            <div class="flex flex-col sm:flex-row items-start sm:items-center w-full sm:w-auto gap-3">
                <a
                    wire:navigate
                    href="{{ route('super-admin.automation-campaigns.create') }}"
                    class="btn text-white bg-primary hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                >
                    <div class="flex gap-x-1 items-center">
                        <x-lucide-circle-plus class="size-5"/>
                        <span>{{ __('Create') }}</span>
                    </div>
                </a>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                    <x-search-box
                        name="search"
                        wire:model.live.debounce.400="search"
                        placeholder="{{ __('Search') }}"
                        :description="__('You can search by communication code and template name.')"
                    />
                </div>
            </div>
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="status" :$sortCol :$sortAsc class="lg:w-4">{{ __('Status') }}</x-table.th>
                        <x-table.th class="lg:w-1/6">{{ __('Description') }}</x-table.th>
                        <x-table.th column="email_template_name" :$sortCol :$sortAsc>{{ __('Email Template') }}</x-table.th>
                        <x-table.th column="sms_template_name" :$sortCol :$sortAsc>{{ __('SMS Template') }}</x-table.th>
                        <x-table.th column="frequency" :$sortCol :$sortAsc>{{ __('Frequency') }}</x-table.th>
                        <x-table.th column="enabled" :$sortCol :$sortAsc class="lg:w-24">{{ __('Enabled') }}</x-table.th>
                        <x-table.th class="lg:w-1/6">{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse($automationCampaigns as $key => $automationCampaign)
                        @php
                            $frequency = (string) str($automationCampaign->frequency->name)->title();
                            $time = ['time' => $automationCampaign->start_at->formatWithTimezone(format: 'H:i')];
                            $frequency = match ($automationCampaign->frequency) {
                                AutomationCampaignFrequency::MONTHLY => __(':frequency on :date at :time', ['frequency' => $frequency, 'date' => now()->format('M ') . $automationCampaign->start_at->format('d, ') . now()->format('Y '), ...$time]),
                                AutomationCampaignFrequency::WEEKLY => __(':frequency on :week at :time', ['frequency' => $frequency, 'week' => data_get($automationCampaign->weekly, Carbon\Carbon::getDays(), ''), ...$time]),
                                AutomationCampaignFrequency::DAILY => __(':frequency at :time', ['frequency' => $frequency, ...$time]),
                                AutomationCampaignFrequency::HOURLY => __('Every :hourly Hours', ['hourly' => $automationCampaign->hourly]),
                                AutomationCampaignFrequency::ONCE => __('One Time on :date at :time', ['date' => $automationCampaign->start_at->formatWithTimezone(), ...$time]),
                            };
                        @endphp
                        <x-table.tr>
                            <x-table.td>{{ $automationCampaign->communicationStatus->code }}</x-table.td>
                            <x-table.td>
                                <span
                                    x-tooltip.placement.right="@js($automationCampaign->communicationStatus->description)"
                                    class="hover:underline cursor-pointer"
                                >
                                    {{ (string) str($automationCampaign->communicationStatus->description)->words(3) }}
                                </span>
                            </x-table.td>
                            <x-table.td>{{ (string) str($automationCampaign->communicationStatus->emailTemplate->name)->words(3) }}</x-table.td>
                            <x-table.td>{{ (string) str($automationCampaign->communicationStatus->smsTemplate->name)->words(3) }}</x-table.td>
                            <x-table.td class="text-primary font-semibold">{{ $frequency }}</x-table.td>
                            <x-table.td>
                                <x-form.button
                                    type="button"
                                    class="uppercase text-xs sm:text-sm+ px-3 py-1.5 disabled:opacity-50"
                                    :variant="$automationCampaign->enabled ? 'primary' : 'error'"
                                    wire:click="updateEnabled({{ $automationCampaign->id }})"
                                    wire:loading.attr="disabled"
                                    wire:key="{{ str()->random(10) }}"
                                >
                                    <x-lucide-loader-2
                                        class="size-4.5 sm:size-5 animate-spin"
                                        wire:target="updateEnabled({{ $automationCampaign->id }})"
                                        wire:loading
                                    />
                                    @if ($automationCampaign->enabled)
                                        <x-lucide-pause
                                            wire:loading.remove
                                            wire:target="updateEnabled({{ $automationCampaign->id }})"
                                            class="size-4.5 sm:size-5 mr-1"
                                        />
                                        <span>{{ __('Pause') }}</span>
                                    @else
                                        <x-lucide-play
                                            wire:loading.remove
                                            wire:target="updateEnabled({{ $automationCampaign->id }})"
                                            class="size-4.5 sm:size-5 mr-1"
                                        />
                                        <span>{{ __('Resume') }}</span>
                                    @endif
                                </x-form.button>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex space-x-2">
                                    <x-dialog>
                                        <x-dialog.open>
                                            <x-form.button
                                                class="text-xs sm:text-sm+ px-3 py-1.5"
                                                type="button"
                                                variant="success"
                                            >
                                                <div class="flex space-x-1 items-center">
                                                    <x-heroicon-o-eye class="size-4.5 sm:size-5 text-white"/>
                                                    <span>{{ __('View') }}</span>
                                                </div>
                                            </x-form.button>
                                        </x-dialog.open>

                                        <div x-data="{ templateType: @js(AutomatedTemplateType::EMAIL->value) }">
                                            <x-dialog.panel size="xl">
                                                <x-slot name="heading">
                                                    <div x-show="templateType === @js(AutomatedTemplateType::EMAIL->value)">
                                                        {{ __(':name Preview', ['name' => (string) str(AutomatedTemplateType::EMAIL->name)->title()]) }}
                                                    </div>
                                                    <div x-show="templateType === @js(AutomatedTemplateType::SMS->value)">
                                                        {{ __(':name Preview', ['name' => AutomatedTemplateType::SMS->name]) }}
                                                    </div>
                                                </x-slot>
                                                <div class="tabs-list flex items-center justify-center rounded bg-slate-200 text-sm text-slate-500 leading-none border-2 border-slate-200 w-36 mx-auto">
                                                    <button
                                                        x-on:click="templateType = @js(AutomatedTemplateType::EMAIL->value)"
                                                        class="btn py-1.5 px-3 shrink-0 text-xs+ font-medium rounded-sm"
                                                        :class="templateType === '{{ AutomatedTemplateType::EMAIL->value }}' ? 'bg-white shadow' : 'hover:text-slate-800 focus:text-slate-800'"
                                                    >
                                                        <x-lucide-mail class="size-3 mr-2"/>
                                                        {{ __('Email') }}
                                                    </button>
                                                    <button
                                                        x-on:click="templateType = @js(AutomatedTemplateType::SMS->value)"
                                                        class="btn py-1.5 px-3 shrink-0 text-xs+ font-medium rounded-sm"
                                                        :class="templateType === @js(AutomatedTemplateType::SMS->value) ? 'bg-white shadow' : 'hover:text-slate-800 focus:text-slate-800'"
                                                    >
                                                        <x-lucide-message-circle class="size-3 mr-2"/>
                                                        {{ __('SMS') }}
                                                    </button>
                                                </div>
                                                <div
                                                    x-show="templateType === @js(AutomatedTemplateType::EMAIL->value)"
                                                    class="mt-4"
                                                >
                                                    <x-creditor.email.preview
                                                        :subject="$automationCampaign->communicationStatus->emailTemplate->subject"
                                                        :content="$automationCampaign->communicationStatus->emailTemplate->content"
                                                        :from="null"
                                                    />
                                                </div>
                                                <div
                                                    x-show="templateType === @js(AutomatedTemplateType::SMS->value)"
                                                    class="mt-4"
                                                >
                                                    <x-creditor.sms.preview
                                                        :content="$automationCampaign->communicationStatus->smsTemplate->content"
                                                    />
                                                </div>
                                                <x-slot name="footer" class="mt-4">
                                                    <x-dialog.close>
                                                        <x-form.default-button type="button">
                                                            {{ __('Close') }}
                                                        </x-form.default-button>
                                                    </x-dialog.close>
                                                </x-slot>
                                            </x-dialog.panel>
                                        </div>
                                    </x-dialog>
                                    <a
                                        wire:navigate
                                        href="{{ route('super-admin.automation-campaigns.edit', $automationCampaign->id) }}"
                                        class="text-xs sm:text-sm+ px-3 py-1.5 btn text-white bg-info hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                                    >
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-o-pencil-square class="size-4.5 sm:size-5"/>
                                            <span>{{ __('Edit') }}</span>
                                        </div>
                                    </a>
                                    <x-confirm-box
                                        :message="__('Are you sure you want to delete this campaign?')"
                                        :ok-button-label="__('Delete')"
                                        action="delete({{ $automationCampaign->id }})"
                                    >
                                        <x-form.button class="text-xs sm:text-sm+ px-3 py-1.5" type="button" variant="error">
                                            <div class="flex space-x-1 items-center">
                                                <x-heroicon-o-trash class="size-4.5 sm:size-5"/>
                                                <span>{{ __('Delete') }}</span>
                                            </div>
                                        </x-form.button>
                                    </x-confirm-box>
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="7"/>
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$automationCampaigns"/>
    </div>
</div>
