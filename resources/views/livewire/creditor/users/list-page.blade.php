<div>
    <div class="card">
        <div class="flex p-4 sm:items-center justify-end gap-4">
            <a
                wire:navigate
                href="{{ route('creditor.users.create') }}"
                class="btn text-sm+ bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 flex items-center space-x-1"
            >
                <x-lucide-circle-plus class="size-5" />
                <span>{{ __('Create') }}</span>
            </a>
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="role" :$sortCol :$sortAsc>{{ __('User Access') }}</x-table.th>
                        <x-table.th column="first-name" :$sortCol :$sortAsc>{{ __('First Name') }}</x-table.th>
                        <x-table.th column="last-name" :$sortCol :$sortAsc>{{ __('Last Name') }}</x-table.th>
                        <x-table.th column="email" :$sortCol :$sortAsc>{{ __('Email') }}</x-table.th>
                        <x-table.th column="status" :$sortCol :$sortAsc>{{ __('Status') }}</x-table.th>
                        <x-table.th class="w-1/12">{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse($users as $user)
                        <x-table.tr @class(['border-b-0' => $loop->last])>
                            <x-table.td>
                                {{ $user->parent_id ? __('All Access') : __('All Access - Master') }}
                            </x-table.td>
                            @php
                                $nameParts = explode(' ', $user->name);
                                $firstName = $nameParts[0] ?? 'N/A';
                                $lastName = $nameParts[1] ?? 'N/A';
                            @endphp
                            <x-table.td>
                                {{ $firstName }}
                            </x-table.td>
                            <x-table.td>
                                {{ $lastName }}
                            </x-table.td>
                            <x-table.td>
                                {{ $user->email }}
                            </x-table.td>
                            <x-table.td>
                                <span class="badge bg-info/20 text-info">{{ __('Active') }}</span>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex gap-x-2">
                                    <a
                                        wire:navigate
                                        href="{{ route('creditor.users.edit', ['user' => $user->id]) }}"
                                        class="text-xs sm:text-sm+ px-3 py-1.5 btn text-white bg-info hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                                    >
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-o-pencil-square class="size-4.5 sm:size-5"/>
                                            <span>{{ __('Edit') }}</span>
                                        </div>
                                    </a>
                                    @if($user->email_verified_at === null)
                                        <x-form.button
                                            wire:click="resend({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50"
                                            wire:target="resend({{ $user->id }})"
                                            class="text-xs sm:text-sm+ px-3 py-1.5"
                                            type="button"
                                            variant="primary"
                                        >
                                            <div class="flex space-x-1 items-center">
                                                <x-lucide-rotate-cw
                                                    wire:loading.class="animate-spin"
                                                    wire:target="resend({{ $user->id }})"
                                                    class="size-4.5 sm:size-5"
                                                />
                                                <span>{{ __('Resend') }}</span>
                                            </div>
                                        </x-form.button>
                                    @endif
                                    @if ($user->id !== auth()->user()->id)
                                        <x-confirm-box
                                            :message="__('Are you sure you want to delete this user?')"
                                            :ok-button-label="__('Delete')"
                                            action="delete({{ $user->id }})"
                                        >
                                            <x-form.button class="text-xs sm:text-sm+ px-3 py-1.5" type="button" variant="error">
                                                <div class="flex space-x-1 items-center">
                                                    <x-heroicon-o-trash class="size-4.5 sm:size-5"/>
                                                    <span>{{ __('Delete') }}</span>
                                                </div>
                                            </x-form.button>
                                        </x-confirm-box>
                                    @endif
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="6" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
    </div>
</div>
