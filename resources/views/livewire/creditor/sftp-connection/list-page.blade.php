<div>
    <div class="card">
        <div 
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $sftpConnections->isNotEmpty(),
                'justify-end' => $sftpConnections->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$sftpConnections" />
            <div class="flex flex-col sm:flex-row items-start sm:items-center w-full sm:w-auto gap-3">
                <a
                    wire:navigate
                    href="{{ route('creditor.sftp.create') }}"
                    class="btn text-sm+ bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 flex items-center space-x-1"
                >
                    <x-lucide-circle-plus class="size-5" />
                    <span>{{ __('Create') }}</span>
                </a>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                    <x-search-box
                        name="search"
                        wire:model.live.debounce.400="search"
                        placeholder="{{ __('Search') }}"
                        :description="__('You can search by its name and username.')"
                    />
                </div>
            </div>
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th>{{ __('Name') }}</x-table.th>
                        <x-table.th>{{ __('Host') }}</x-table.th>
                        <x-table.th>{{ __('Port') }}</x-table.th>
                        <x-table.th>{{ __('Username') }}</x-table.th>
                        <x-table.th>{{ __('Used for?') }}</x-table.th>
                        <x-table.th>{{ __('Enabled/Disabled') }}</x-table.th>
                        <x-table.th>{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($sftpConnections as $sftpConnection)
                        <x-table.tr>
                            <x-table.td>{{ $sftpConnection->name }}</x-table.td>
                            <x-table.td>{{ $sftpConnection->host }}</x-table.td>
                            <x-table.td>{{ $sftpConnection->port }}</x-table.td>
                            <x-table.td>{{ $sftpConnection->username }}</x-table.td>
                            <x-table.td>
                                @if ($sftpConnection->export_filepath && $sftpConnection->import_filepath)
                                    {{ __('Both (Import and Export)') }}
                                @elseif ($sftpConnection->export_filepath)
                                    {{ __('Export only') }}
                                @elseif ($sftpConnection->import_filepath)
                                    {{ __('Import only') }}
                                @endif
                            </x-table.td>
                            <x-table.td>
                                <x-form.switch
                                    wire:change="toggleEnabled({{ $sftpConnection->id }})"
                                    name="enabled-{{ $sftpConnection->id }}"
                                    :checked="$sftpConnection->enabled"
                                />
                            </x-table.td>
                            <x-table.td class="text-center flex gap-x-2">
                                <a
                                    wire:navigate
                                    href="{{ route('creditor.sftp.edit', ['sftp' => $sftpConnection->id]) }}"
                                    class="text-xs sm:text-sm+ px-3 py-1.5 btn text-white bg-info hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                                >
                                    <div class="flex space-x-1 items-center">
                                        <x-heroicon-o-pencil-square class="size-4.5 sm:size-5"/>
                                        <span>{{ __('Edit') }}</span>
                                    </div>
                                </a>
                                <x-confirm-box
                                    :message="__('Are you sure you want to delete this sftp connection?')"
                                    :ok-button-label="__('Delete')"
                                    action="delete({{ $sftpConnection->id }})"
                                >
                                    <x-form.button class="text-xs sm:text-sm+ px-3 py-1.5" type="button" variant="error">
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-o-trash class="size-4.5 sm:size-5"/>
                                            <span>{{ __('Delete') }}</span>
                                        </div>
                                    </x-form.button>
                                </x-confirm-box>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found colspan="7" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$sftpConnections" />
    </div>
</div>
