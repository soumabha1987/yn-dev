<div>
    <div class="card">
        <div class="py-3 px-4 sm:px-5">
            <h2 class="text-md text-black font-semibold lg:text-lg">
                {{ __('SFTP Import') }}
            </h2>
        </div>
        <x-table>
            <x-slot name="tableHead">
                <x-table.th>{{ __('Header name') }}</x-table.th>
                <x-table.th>{{ __('SFTP Connection') }}</x-table.th>
            </x-slot>
            <x-slot name="tableBody">
                @forelse ($headers as $header)
                    <x-table.tr @class(['border-none' => $loop->last])>
                        <x-table.td>
                            {{ str($header->name)->title()->headline() }}
                        </x-table.td>
                        <x-table.td>
                            <x-form.select
                                x-bind:value="{{ (int) $header->sftp_connection_id }}"
                                :options="$sftpConnections"
                                wire:change="attach($event.target.value, {{ $header->id }})"
                                class="mb-1.5"
                            >
                                <x-slot name="blankOption">
                                    <option value="">{{ __('No SFTP') }}</option>
                                </x-slot>
                            </x-form.select>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found colspan="2" />
                @endforelse
            </x-slot>
        </x-table>
    </div>
</div>
