@use('App\Enums\FileUploadHistoryType')

<div>

    @if ($companyIsNotVerified)
        <template x-if="!displayWarningModal">
            <x-import-consumers.company-not-verified-modal />
        </template>
    @endif

    <div @class([
        'card',
        'sm:w-1/2' => ! $selectedHeader,
    ])>
        <div @class([
            'grid grid-cols-1 gap-5 px-5',
            'sm:grid-cols-2' => $selectedHeader,
            'sm:grid-cols-1' => ! $selectedHeader,
        ])>
            <div class="mt-3 sm:mt-0">
                <form wire:submit="importConsumers" autocomplete="off">
                    <div class="my-4">
                        <x-form.select
                            wire:model.live="form.header"
                            :options="$csvHeaders->pluck('name', 'id')->all()"
                            name="form.header"
                            :label="__('Select Uploaded Header')"
                            :placeholder="__('Uploaded Header')"
                            required
                        />
                    </div>

                    <div class="my-4">
                        <x-form.input-field
                            wire:model="form.import_file"
                            label="{{ __('Select Import File') }}"
                            name="form.import_file"
                            type="file"
                            class="w-full"
                            accept=".csv"
                            required
                        />
                    </div>
                    <x-loader
                        wire:loading
                        wire:target="importConsumers, form.import_file"
                    />

                    <div class="my-4">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Select Action') }}<span class="text-error text-base">*</span>
                        </span>

                        <div class="flex flex-col space-y-2 mt-2">
                            <x-form.input-radio
                                wire:model="form.import_type"
                                value="{{ FileUploadHistoryType::ADD->value }}"
                                class="text-xs+ whitespace-nowrap"
                            >
                                <x-slot name="label">
                                    <div class="flex space-x-1 items-center justify-between">
                                        <span class="text-slate-800 font-semibold">{{ FileUploadHistoryType::ADD->displayMessage() }}</span>&nbsp;
                                        <span x-tooltip.placement.right="@js(__('(minimum fields: required YN fields)'))">
                                            <x-lucide-circle-help class="size-4 hover:text-gray-500" />
                                        </span>
                                    </div>
                                </x-slot>
                            </x-form.input-radio>

                            <x-form.input-radio
                                wire:model="form.import_type"
                                value="{{ FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB->value }}"
                                class="text-xs+ whitespace-nowrap"
                            >
                                <x-slot name="label">
                                    <div class="flex space-x-1 items-center justify-between">
                                        <span class="text-slate-800 font-semibold">{{ FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB->displayMessage() }}</span>&nbsp;
                                        <span x-tooltip.placement.right="@js(__('(minimum fields: required YN fields)'))">
                                            <x-lucide-circle-help class="size-4 hover:text-gray-500" />
                                        </span>
                                    </div>
                                </x-slot>
                            </x-form.input-radio>

                            <x-form.input-radio
                                wire:model="form.import_type"
                                value="{{ FileUploadHistoryType::DELETE->value }}"
                                class="text-xs+"
                            >
                                <x-slot name="label">
                                    <div class="flex space-x-1 items-center justify-between">
                                        <span class="text-slate-800 font-semibold">{{ FileUploadHistoryType::DELETE->displayMessage() }}</span>&nbsp;
                                        <span x-tooltip.placement.right="@js(__('(minimum fields: original account number)'))">
                                            <x-lucide-circle-help class="size-4 hover:text-gray-500" />
                                        </span>
                                    </div>
                                </x-slot>
                            </x-form.input-radio>

                            <x-form.input-radio
                                wire:model="form.import_type"
                                class="text-xs+"
                                value="{{ FileUploadHistoryType::UPDATE->value }}"
                            >
                                <x-slot name="label">
                                    <div class="flex space-x-1 items-center justify-between">
                                        <span class="text-slate-800 font-semibold">{{ FileUploadHistoryType::UPDATE->displayMessage() }}</span>&nbsp;
                                        <span x-tooltip.placement.right="@js(__('(minimum fields: original account number and updated fields)'))">
                                            <x-lucide-circle-help class="size-4 hover:text-gray-500" />
                                        </span>
                                    </div>
                                </x-slot>
                            </x-form.input-radio>
                            @error('form.import_type')
                                <div class="mt-2">
                                    <span class="text-error text-sm+">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="my-4">
                        <x-form.button
                            type="submit"
                            variant="primary"
                            wire:target="importConsumers, form.import_file"
                            wire:loading.attr="disabled"
                            class="disabled:opacity-50"
                        >
                            <x-lucide-loader-2
                                wire:loading
                                wire:target="importConsumers"
                                class="size-5 animate-spin mr-2"
                            />
                            {{ __('Submit') }}
                        </x-form.button>
                    </div>
                </form>
            </div>

            @if ($selectedHeader)
                <div class="my-5">
                    <x-table
                        :disable-loader="true"
                        class="text-sm+"
                    >
                        <x-slot name="tableHead" class="border-x">
                            <x-table.tr>
                                <x-table.th>{{ __('Your Uploaded Headers') }}</x-table.th>
                                <x-table.th>{{ __('YouNegotiate Data Field') }}</x-table.th>
                            </x-table.tr>
                        </x-slot>
                        <x-slot name="tableBody" class="border-x">
                            @foreach ($selectedHeader['headers'] as $key => $header)
                                <x-table.tr>
                                    <x-table.td>{{ $key }}</x-table.td>
                                    <x-table.td>{{ $header ?? '-' }}</x-table.td>
                                </x-table.tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                </div>
            @endif
        </div>
    </div>
</div>
