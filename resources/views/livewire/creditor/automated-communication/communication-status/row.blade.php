@use('App\Enums\CommunicationStatusTriggerType')

<x-table.tr
    x-data="templateEdit"
    @class(['border-b-0' => $loop->last])
>
    <x-table.td>
        {{ $communicationStatus->code->value }}
    </x-table.td>
    <x-table.td>
        <span
            x-tooltip.placement.right="@js($communicationStatus->description)"
            class="hover:underline cursor-pointer"
        >
            {{ str($communicationStatus->description)->words(3)->toString() }}
        </span>
    </x-table.td>
    <x-table.td>
        <div
            x-on:refresh-email.window="reset()"
            class="flex items-center space-x-2"
        >
            <template x-if="! editEmailTemplate">
                <span @class(['text-error' => ! $communicationStatus->emailTemplate])>
                    {{ $communicationStatus->emailTemplate ? str($communicationStatus->emailTemplate->name)->words(3) : 'N/A' }}
                </span>
            </template>

            <template x-if="editEmailTemplate">
                <select
                    name="automated_email_template_id"
                    wire:model.change="automated_email_template_id"
                    placeholder="{{ __('Email Template') }}"
                    class="form-select capitalize mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 invalid:text-slate-500 hover:border-slate-400 focus:border-primary"
                    required
                >
                    <option value="" disabled>{{ __('Select Email Template') }}</option>
                    @foreach($emailTemplateDropdownList as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </template>

            <template x-if="! editEmailTemplate">
                <button
                    type="button"
                    x-on:click="editEmailTemplate = ! editEmailTemplate"
                    class="btn text-primary hover:text-primary-focus"
                    x-tooltip.placement.bottom="@js(__('Edit Email Template'))"
                >
                    <x-lucide-edit class="size-4"/>
                </button>
            </template>
        </div>
    </x-table.td>
    <x-table.td>
        <div
            x-on:refresh-sms.window="reset"
            class="flex items-center space-x-2"
        >
            <template x-if="! editSmsTemplate">
                <span @class(['text-error' => ! $communicationStatus->smsTemplate ])>
                    {{ $communicationStatus->smsTemplate ? str($communicationStatus->smsTemplate->name)->words(3) : 'N/A' }}
                </span>
            </template>

            <template x-if="editSmsTemplate">
                <select
                    name="automated_sms_template_id"
                    wire:model.change="automated_sms_template_id"
                    placeholder="{{ __('SMS Template') }}"
                    class="form-select capitalize mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 invalid:text-slate-500 hover:border-slate-400 focus:border-primary"
                    required
                >
                    <option value="" disabled>{{ __('Select SMS Template') }}</option>
                    @foreach($smsTemplateDropdownList as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </template>

            <template x-if="! editSmsTemplate">
                <button
                    type="button"
                    x-on:click="editSmsTemplate = ! editSmsTemplate"
                    class="btn text-primary hover:text-primary-focus"
                    x-tooltip.placement.bottom="@js(__('Edit SMS Template'))"
                >
                    <x-lucide-edit class="size-4"/>
                </button>
            </template>
        </div>
    </x-table.td>
    <x-table.td>
        <span @class([
            'badge font-semibold whitespace-nowrap',
            'bg-success/10 text-success' => $communicationStatus->trigger_type === CommunicationStatusTriggerType::AUTOMATIC,
            'bg-info/10 text-info' => $communicationStatus->trigger_type === CommunicationStatusTriggerType::SCHEDULED,
            'bg-secondary/10 text-secondary' => $communicationStatus->trigger_type === CommunicationStatusTriggerType::BOTH,
        ])>
            {{ $communicationStatus->trigger_type->name }}
        </span>
    </x-table.td>
</x-table.tr>

@script
    <script>
        Alpine.data('templateEdit', () => ({
            editEmailTemplate: false,
            editSmsTemplate: false,
            reset() {
                this.editEmailTemplate = false
                this.editSmsTemplate = false
            },
        }))
    </script>
@endscript
