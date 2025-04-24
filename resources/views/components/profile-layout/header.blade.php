@use('Illuminate\Support\Facades\Storage')
@use('App\Enums\Role')
@use('App\Enums\CompanyStatus')

<div>
    <div class="bg-slate-50 border border-b-1">
        <nav class="before:bg-white print:hidden">
            <div x-data="{ profilePhoto: '{{ auth()->user()->image ? Storage::url('profile-images/' . auth()->user()->image) : null }}' }" class="relative px-4 sm:px-12 py-2  flex w-full items-center justify-between">
                <div class="items-center justify-between w-full flex">
                    <div class="flex items-center">
                        <livewire:creditor.logo />
                    </div>
                    <div class="-mr-1.5 flex items-center space-x-2">
                        <x-popover>
                            <x-popover.button class="p-0 rounded-full hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25">
                                <div x-on:profile-photo-updated.window="profilePhoto = $event.detail" class="size-10 avatar">
                                    <div x-show="profilePhoto !== ''">
                                        <img :src="profilePhoto" class="rounded-full object-fit object-cover size-10" alt="{{ __('avatar') }}">
                                    </div>
                                    <div x-show="profilePhoto === ''" class="text-base uppercase border rounded-full is-initial border-primary/30 bg-primary/10 text-primary">
                                        {{ $firstLetterOfName = substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                </div>
                            </x-popover.button>

                            <x-popover.panel class="w-64 bg-white border rounded-lg border-slate-150 shadow-soft">
                                <div class="flex items-center px-4 py-5 space-x-4 rounded-t-lg bg-slate-100">
                                    <div class="avatar">
                                        <div x-show="profilePhoto !== ''">
                                            <img :src="profilePhoto" class="rounded-full object-fit object-cover size-10" alt="{{ __('avatar') }}">
                                        </div>
                                        <div x-show="profilePhoto === ''" class="text-sm uppercase border rounded-full is-initial border-primary/30 bg-primary/10 text-primary">
                                            {{ $firstLetterOfName }}
                                        </div>
                                    </div>

                                    <div>
                                        <span class="text-base font-medium text-slate-700">
                                            {{ auth()->user()->name }}
                                        </span>
                                        <p class="text-xs text-slate-400">
                                            {{ auth()->user()->roles->first()?->name }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-col pt-2 pb-5">
                                    <a wire:navigate href="{{ route('change-password') }}" class="flex items-center px-4 py-2 space-x-3 tracking-wide transition-all outline-none group hover:bg-slate-100 focus:bg-slate-100">
                                        <div class="flex items-center justify-center size-8 text-white rounded-lg bg-accent">
                                            <x-heroicon-o-key class="size-5" />
                                        </div>
                                        <h2 class="font-medium transition-colors text-slate-700 group-hover:text-primary group-focus:text-primary">
                                            {{ __('Change Password') }}
                                        </h2>
                                    </a>

                                    <div class="px-4 mt-3">
                                        <livewire:creditor.auth.logout />
                                    </div>
                                </div>
                            </x-popover.panel>
                        </x-popover>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</div>
