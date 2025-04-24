@use('App\Enums\FileUploadHistoryType')
@use('App\Enums\FileUploadHistoryStatus')

<div>
    <div class="card">
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $fileUploadHistories->isNotEmpty(),
                'justify-end' => $fileUploadHistories->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$fileUploadHistories" />
            <x-form.select
                wire:model.live="typeFilter"
                :placeholder="__('File Upload History Type')"
                :options="collect(FileUploadHistoryType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->displayMessage()])->toArray()"
                name="typeFilter"
                class="!mt-0"
            />
        </div>

        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table class="w-fit">
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="upload-date" :$sortCol :$sortAsc>{{ __('Upload Date') }}</x-table.th>
                        <x-table.th class="text-center" column="sftp-import" :$sortCol :$sortAsc>{{ __('SFTP Import') }}</x-table.th>
                        <x-table.th column="name" :$sortCol :$sortAsc>{{ __('File Name') }}</x-table.th>
                        <x-table.th column="type" :$sortCol :$sortAsc>{{ __('Upload Type') }}</x-table.th>
                        <x-table.th class="text-center" column="records" :$sortCol :$sortAsc>{{ __('#Records') }}</x-table.th>
                        <x-table.th class="text-center" column="successful-records" :$sortCol :$sortAsc>{{ __('#Successful') }}</x-table.th>
                        <x-table.th class="text-center" column="failed-records" :$sortCol :$sortAsc>{{ __('#Failed') }}</x-table.th>
                        <x-table.th class="text-center" column="status" :$sortCol :$sortAsc>{{ __('Status') }}</x-table.th>
                        <x-table.th class="lg:w-1/12">{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($fileUploadHistories as $fileUploadHistory)
                        <x-table.tr>
                            <x-table.td class="whitespace-nowrap">{{ $fileUploadHistory->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td>
                                <div class="flex justify-center">
                                    @if($fileUploadHistory->is_sftp_import)
                                        <x-lucide-check class='size-4.5 sm:size-5 text-success' />
                                    @else
                                        <x-lucide-x class='size-4.5 sm:size-5 text-error' />
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td>
                                <span
                                    wire:poll.5s="$refresh"
                                    x-tooltip.placement.bottom="@js($fileUploadHistory->filename)"
                                    class="hover:underline whitespace-nowrap"
                                >
                                    {{ str($fileUploadHistory->filename)->limit(15)->toString() }}
                                </span>
                            </x-table.td>
                            <x-table.td class="whitespace-nowrap">
                                <span
                                    @class([
                                        "text-center badge whitespace-nowrap",
                                        "bg-success/10 text-success" => $fileUploadHistory->type === FileUploadHistoryType::ADD,
                                        "bg-secondary/10 text-secondary" => $fileUploadHistory->type === FileUploadHistoryType::UPDATE,
                                        "bg-error/10 text-error" => $fileUploadHistory->type === FileUploadHistoryType::DELETE,
                                        "bg-primary/10 text-primary" => $fileUploadHistory->type === FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB,
                                    ])
                                >
                                    {{ $fileUploadHistory->type->fileHistoriesDisplayMessage() }}
                                </span>
                            </x-table.td>
                            <x-table.td class="text-center">{{ $fileUploadHistory->total_records }}</x-table.td>
                            <x-table.td class="text-center">{{ $fileUploadHistory->processed_count }}</x-table.td>
                            <x-table.td class="text-center">{{ $fileUploadHistory->failed_count }}</x-table.td>
                            <x-table.td class="text-center">
                                <span
                                    @class([
                                        "text-center badge whitespace-nowrap",
                                        "bg-success/10 text-success" => $fileUploadHistory->status === FileUploadHistoryStatus::COMPLETE,
                                        "bg-error/10 text-error" => $fileUploadHistory->status === FileUploadHistoryStatus::FAILED,
                                        "bg-primary/10 text-primary" => $fileUploadHistory->status === FileUploadHistoryStatus::VALIDATING,
                                    ])
                                >
                                    {{ $fileUploadHistory->status->displayStatus() }}
                                </span>
                            </x-table.td>
                            <x-table.td class="text-nowrap space-x-2">
                                <x-menu>
                                    <x-menu.button
                                        class="hover:bg-slate-100 p-1 rounded-full"
                                        x-on:close-menu.window="menuOpen = false"
                                    >
                                        <x-heroicon-m-ellipsis-horizontal class="size-7" />
                                    </x-menu.button>
                                    <x-menu.items>
                                        <x-menu.item
                                            wire:click="downloadUploadedFile({{ $fileUploadHistory->id }})"
                                            wire:target="downloadUploadedFile({{ $fileUploadHistory->id }})"
                                            wire:loading.attr="disabled"
                                            class="flex items-center gap-3"
                                        >
                                            <x-lucide-loader-2
                                                wire:loading
                                                wire:target="downloadUploadedFile({{ $fileUploadHistory->id }})"
                                                class="size-5 animate-spin"
                                            />
                                            <x-lucide-download
                                                wire:loading.remove
                                                wire:target="downloadUploadedFile({{ $fileUploadHistory->id }})"
                                                class="size-5"
                                            />
                                            <span>{{ __('View Upload File') }}</span>
                                        </x-menu.item>

                                        <x-menu.item
                                            wire:click="downloadFailedFile({{ $fileUploadHistory->id }})"
                                            wire:target="downloadFailedFile({{ $fileUploadHistory->id }})"
                                            wire:loading.attr="disabled"
                                            class="flex items-center gap-3"
                                        >
                                            <x-lucide-loader-2
                                                wire:loading
                                                wire:target="downloadFailedFile({{ $fileUploadHistory->id }})"
                                                class="size-5 animate-spin"
                                            />
                                            <x-lucide-download
                                                wire:loading.remove
                                                wire:target="downloadFailedFile({{ $fileUploadHistory->id }})"
                                                class="size-5"
                                            />
                                            <span>{{ __('Download Failed File') }}</span>
                                        </x-menu.item>

                                        <x-confirm-box
                                            :message="__('Are you sure you want to delete this record?')"
                                            :ok-button-label="__('Delete')"
                                            action="delete({{ $fileUploadHistory->id }})"
                                        >
                                            <x-menu.close>
                                                <x-menu.item>
                                                    <x-heroicon-o-trash class="size-5" />
                                                    <span>{{ __('Delete') }}</span>
                                                </x-menu.item>
                                            </x-menu.close>
                                        </x-confirm-box>
                                    </x-menu.items>
                                </x-menu>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="8" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$fileUploadHistories" />
    </div>
</div>
