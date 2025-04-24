<div>
    @foreach($users as $user)
        <x-table.tr>
            <x-table.td>{{ str($user->name)->title() }}</x-table.td>
            <x-table.td>{{ $user->parent->name ?? 'N/A' }}</x-table.td>
            <x-table.td>{{ $user->email }}</x-table.td>
            <x-table.td>{{ $user->phone_no ?? 'N/A' }}</x-table.td>
            <x-table.td>
                @if ($user->blocked_at && $user->blocker_user_id)
                    <span class="badge bg-warning/20 text-warning">{{ __('Blocked') }}</span>
                @else
                    <span class="badge bg-info/20 text-info">{{ __('Active') }}</span>
                @endif
            </x-table.td>
            <x-table.td>
                <span
                    x-tooltip.placement.left="@js($user->subclient ? str($user->subclient->subclient_name)->title() : str($user->company->company_name)->title())"
                    class="hover:underline cursor-pointer"
                >
                    {{ $user->subclient ? str($user->subclient->subclient_name)->title()->words(3) : str($user->company->company_name)->title()->words(3) }}
                </span>
            </x-table.td>
            <x-table.td>{{ $user->subclient ? __('Subclient') : __('Company') }}</x-table.td>
            <x-table.td>
                <x-menu>
                    <x-menu.button class="hover:bg-slate-100 p-1 rounded-full">
                        <x-heroicon-m-ellipsis-horizontal class="size-7" />
                    </x-menu.button>
                    <x-menu.items>
                        @if ($user->blocked_at === null && $user->blocker_user_id === null)
                            <div @close-menu-item.window="menuOpen = false">
                                <x-menu.item
                                    wire:loading.attr="disabled"
                                    wire:click="forgotPassword({{ $user->id }})"
                                    wire:target="forgotPassword({{ $user->id }})"
                                >
                                    <x-lucide-loader-2
                                        wire:loading
                                        wire:target="forgotPassword({{ $user->id }})"
                                        class="size-5 animate-spin"
                                    />
                                    <x-lucide-key
                                        wire:loading.remove
                                        wire:target="forgotPassword({{ $user->id }})"
                                        class="size-5"
                                    />
                                    <span>{{ __('Forgot Password') }}</span>
                                </x-menu.item>
                            </div>
                        @endif
                        <x-confirm-box
                            :message="$user->blocked_at ? __('Do you want to unblock this user? ') : __('Do you want to block this user?')"
                            :ok-button-label="$user->blocked_at ? __('Unblock') : __('Block')"
                            action="toggleBlock({{ $user->id }})"
                        >
                            <x-menu.close>
                                <x-menu.item>
                                    <span class="size-5">
                                        @svg($user->blocked_at ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                                    </span>
                                    <span>{{ $user->blocked_at ? __('Unblock') : __('Block') }}</span>
                                </x-menu.item>
                            </x-menu.close>
                        </x-confirm-box>
                        <x-confirm-box
                            :message="__('Are you sure you want to delete this user?')"
                            :ok-button-label="__('Delete')"
                            action="delete({{ $user->id }})"
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
        <x-users.children :users="$user->children" />
    @endforeach
</div>
