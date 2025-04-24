<div>
    <div class="card">
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $users->isNotEmpty(),
                'justify-end' => $users->isEmpty()
            ])
            x-on:refresh-page.window="$wire.$refresh"
        >
            <x-table.per-page-count :items="$users" />
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto gap-3">
                <livewire:creditor.manage-h2-h-users.create :openModel="$create" />
                <x-search-box
                    name="search"
                    wire:model.live.debounce.400="search"
                    placeholder="{{ __('Search') }}"
                    :description="__('You can search by name, email and phone number')"
                />
            </div>
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="name" :$sortCol :$sortAsc>{{ __('Name') }}</x-table.th>
                        <x-table.th>{{ __('Email') }}</x-table.th>
                        <x-table.th>{{ __('Phone') }}</x-table.th>
                        <x-table.th class="sm:w-1/12">{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($users as $user)
                        <x-table.tr>
                            <x-table.td>{{ $user->name }}</x-table.td>
                            <x-table.td>{{ $user->email }}</x-table.td>
                            <x-table.td>{{ $user->phone_no ? Str::formatPhoneNumber($user->phone_no) : 'N/A' }}</x-table.td>
                            <x-table.td>
                                <div class="flex space-x-2">
                                    <livewire:creditor.manage-h2-h-users.edit :key="str()->random()" :$user />
                                    <x-confirm-box
                                        :message="__('Are you sure you want to delete this user?')"
                                        :ok-button-label="__('Delete')"
                                        action="delete({{ $user->id }})"
                                    >
                                        <x-form.button
                                            type="button"
                                            variant="error"
                                            class="text-xs sm:text-sm space-x-1 px-2 sm:px-3 py-1.5 hover:bg-error-focus"
                                        >
                                            <x-heroicon-o-trash class="size-4.5 sm:size-5" />
                                            <span>{{ __('Delete') }}</span>
                                        </x-form.button>
                                    </x-confirm-box>
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="4" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$users" />
    </div>
</div>
