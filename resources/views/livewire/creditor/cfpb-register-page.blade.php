<div>
    <div
        class="card"
        x-data="cfpbModelData"
    >
        <div
            @class([
                'flex p-4 items-center gap-4',
                'justify-start' => $cfpbFileUploadHistories->isNotEmpty(),
            ])
        >
            <x-table.per-page-count :items="$cfpbFileUploadHistories" />
        </div>
        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th>{{ __('Upload Date') }}</x-table.th>
                        <x-table.th>{{ __('File Name') }}</x-table.th>
                        <x-table.th>{{ __('Consumers') }}</x-table.th>
                        <x-table.th>{{ __('Total Cost') }}</x-table.th>
                        <x-table.th class="w-1/12">{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($cfpbFileUploadHistories as $cfpbFileUploadHistory)
                        <x-table.tr>
                            <x-table.td class="whitespace-nowrap">
                                {{ $cfpbFileUploadHistory->created_at->formatWithTimezone() }}
                            </x-table.td>
                            <x-table.td>
                                <div
                                    wire:click="downloadUploadedFile({{ $cfpbFileUploadHistory->id }})"
                                    class="flex space-x-4 items-center hover:cursor-pointer hover:underline hover:underline-offset-4 hover:text-primary"
                                >
                                    <span
                                        x-tooltip.placement.bottom="@js($cfpbFileUploadHistory->filename)"
                                        class="hover:underline cursor-pointer"
                                    >
                                        {{ str($cfpbFileUploadHistory->filename)->limit(35)->toString() }}
                                    </span>
                                    <x-lucide-download class="size-5" />
                                </div>
                            </x-table.td>
                            <x-table.td class="text-center">{{ $cfpbFileUploadHistory->active_consumers_count }}</x-table.td>
                            <x-table.td class="text-center">{{ Number::currency((float) $cfpbFileUploadHistory->active_consumers_count * $ecoMailAmount ?? 0) }}</x-table.td>
                            <x-table.td class="flex justify-center space-x-2 whitespace-nowrap">
                                <x-form.button
                                    class="text-xs sm:text-sm+ px-3 py-1.5"
                                    type="button"
                                    variant="primary"
                                    x-on:click="secureEcoLetters({{ $cfpbFileUploadHistory->id }}, {{ $cfpbFileUploadHistory->active_consumers_count }}, '{{ Number::currency((float) $cfpbFileUploadHistory->active_consumers_count * $ecoMailAmount ?? 0) }}')"
                                >
                                    <div class="flex space-x-1 items-center">
                                        <x-lucide-mails class="size-4.5 sm:size-5"/>
                                        <span>{{ __('Send Secure EcoLetters') }}</span>
                                    </div>
                                </x-form.button>
                                <x-form.button
                                    class="text-xs sm:text-sm+ px-3 py-1"
                                    type="button"
                                    variant="success"
                                    x-on:click="downloadAndPrintLetters({{ $cfpbFileUploadHistory->id }}, {{ $cfpbFileUploadHistory->active_consumers_count }})"
                                >
                                    <div class="flex space-x-1 items-center">
                                        <x-lucide-cloud-download class="size-4.5 sm:size-5"/>
                                        <span>{{ __('Download & Print Letters') }}</span>
                                    </div>
                                </x-form.button>
                                <x-confirm-box
                                    :message="__('Are you sure you want to delete this cfpb campaign?')"
                                    :ok-button-label="__('Delete')"
                                    action="cfpbDisable({{ $cfpbFileUploadHistory->id }})"
                                >
                                    <x-form.button
                                        class="text-xs sm:text-sm+ px-3 py-1.5"
                                        type="button"
                                        variant="error"
                                    >
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-o-trash class="size-4.5 sm:size-5"/>
                                            <span>{{ __('Delete') }}</span>
                                        </div>
                                    </x-form.button>
                                </x-confirm-box>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="5" />
                    @endforelse
                </x-slot>
            </x-table>
            <x-dialog x-model="openConfirmOrModel">
                <span @close-confirmation-box.window="dialogOpen = false" />
                <x-dialog.panel>
                    <x-slot name="heading">
                        <span x-text="isDownloadAndPrintModel ? @js(__('Download & Print Eco Letters')) : @js(__('Confirm Payment & Send Eco Letters'))"></span>
                    </x-slot>
                    <div class="border">
                        <div class="flex items-center justify-between border-b py-2 px-3 text-sm+ font-semibold">
                            <h3 class="text-black">{{ __("Number of consumers") }}</h3>
                            <p class="text-primary" x-text="activeConsumersCount"></p>
                        </div>
                        <div :class="isEcoLettersModel ? 'border-b py-2 px-3 space-y-2' : 'py-2 px-3 space-y-2'">
                            <x-form.input-radio
                                :label="__('Include a personalized QR code')"
                                wire:model.boolean="withQrCode"
                                value="true"
                            />
                            <x-form.input-radio
                                :label="__('Do not include a personalized QR code')"
                                wire:model.boolean="withQrCode"
                                value="false"
                            />
                        </div>

                        <template x-if="isEcoLettersModel">
                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                <h3 class="text-black">{{ __("Cost") }}</h3>
                                <p class="text-primary" x-text="fileDeductAmount"></p>
                            </div>
                        </template>
                    </div>

                    <template x-if="isEcoLettersModel">
                        <div class="bg-slate-100 border py-2 px-3 my-4 text-sm">
                            <p>{!! __('You are about to send a secure eco-friendly letter to the recipient. A small fee of :amount will be charged for this service.', [
                                'amount' => '<span class="font-semibold" x-text="fileDeductAmount"></span>'
                            ]) !!}</p>
                            <p>{{__("Would you like to proceed?")}}</p>
                        </div>
                    </template>

                    <div class="flex flex-col mt-4 sm:flex-row items-stretch sm:items-center justify-center gap-4">
                        <x-dialog.close>
                            <x-form.default-button
                                type="button"
                                class="w-full sm:w-32"
                            >
                                {{ __('Cancel') }}
                            </x-form.default-button>
                        </x-dialog.close>
                        <div x-show="isEcoLettersModel">
                            <x-form.button
                                type="button"
                                variant="primary"
                                wire:click="secureEcoLetters(fileUploadHistoryId)"
                                @click="dialogOpen = false"
                                class="w-full"
                            >
                                {{ __('Process Payment & Send Letters') }}
                            </x-form.button>
                        </div>
                        <div x-show="isDownloadAndPrintModel">
                            <x-form.button
                                type="button"
                                variant="primary"
                                wire:click="downloadLetters(fileUploadHistoryId)"
                                @click="dialogOpen = false"
                                class="w-full"
                            >
                                {{ __('Download & Print Letters') }}
                            </x-form.button>
                        </div>
                    </div>
                </x-dialog.panel>
            </x-dialog>
        </div>
        <x-loader
            wire:loading
            wire:target.except="cfpbDisable, downloadUploadedFile"
        />
        <x-table.per-page :items="$cfpbFileUploadHistories" />
    </div>
</div>

@script
    <script>
        Alpine.data('cfpbModelData', () => ({
            isEcoLettersModel: false,
            openConfirmOrModel: false,
            isDownloadAndPrintModel: false,
            fileUploadHistoryId: null,
            fileDeductAmount: '',
            activeConsumersCount: '',

            downloadAndPrintLetters(fileUploadHistoryId, activeConsumersCount) {
                this.isDownloadAndPrintModel = true
                this.openConfirmOrModel = true
                this.isEcoLettersModel = false
                this.fileUploadHistoryId = fileUploadHistoryId
                this.activeConsumersCount = activeConsumersCount
                this.$wire.withQrCode = false
            },

            secureEcoLetters(fileUploadHistoryId, activeConsumersCount, amount) {
                this.isEcoLettersModel = true
                this.openConfirmOrModel = true
                this.isDownloadAndPrintModel = false
                this.fileUploadHistoryId = fileUploadHistoryId
                this.activeConsumersCount = activeConsumersCount
                this.fileDeductAmount = amount
                this.$wire.withQrCode = false
            }
        }))
    </script>
@endscript
