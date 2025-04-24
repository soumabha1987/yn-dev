@use('Illuminate\Support\Facades\Storage')

@props([
    'routeName' => 'profile',
])

<div>
    <main class="w-full pb-8">
        <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
            <div class="col-span-12 lg:col-span-4">
                <div class="card p-4 sm:p-5">
                    <div class="flex items-center space-x-4">
                        <div
                            x-data="{ profilePhoto: '{{ auth()->user()->consumerProfile?->image ? Storage::url('profile-images/' . auth()->user()->consumerProfile->image) : null  }}' }"
                            class="avatar size-14"
                            x-on:profile-photo-updated.window="$dispatch('update-profile-photo', $event.detail[0]); profilePhoto = $event.detail[0]"
                        >
                            <template x-if="profilePhoto !== ''">
                                <img
                                    x-bind:src="profilePhoto"
                                    class="rounded-full object-fit object-cover size-14"
                                    alt="{{ __('Avatar') }}"
                                >
                            </template>
                            <template x-if="profilePhoto === ''">
                                <div class="is-initial text-xl rounded-full border border-primary/30 bg-primary/10 uppercase text-primary">
                                    {{ auth()->user()->pluckUsernameFirstTwoDigits }}
                                </div>
                            </template>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-slate-700">
                                {{ auth()->user()->first_name . ' ' . auth()->user()->last_name }}
                            </h3>
                        </div>
                    </div>
                    <ul class="mt-6 space-y-1.5 font-semibold">
                        <a
                            wire:navigate
                            href="{{ route('consumer.profile') }}"
                        >
                            <div
                                @class([
                                    'flex space-x-2 rounded-lg px-4 py-2.5 text-slate-700 ease-in-out delay-100 tracking-wide outline-none transition-all',
                                    'bg-primary text-white items-center' => $routeName === 'profile',
                                    'group hover:bg-slate-100 hover:text-slate-900 focus:bg-slate-100 focus:text-slate-900' => $routeName !== 'profile',
                                ])
                            >
                                <x-lucide-user-circle
                                    @class([
                                        'size-5',
                                        'text-black delay-100 group-hover:text-black group-focus:text-slate-500' => $routeName !== 'profile',
                                    ])
                                />
                                <span>{{ __('Account') }}</span>
                            </div>
                        </a>
                        <a
                            wire:navigate
                            href="{{ route('consumer.communication_controls') }}"
                        >
                            <div
                                @class([
                                    'flex space-x-2 rounded-lg px-4 py-2.5 text-slate-700 ease-in-out delay-100 tracking-wide outline-none transition-all',
                                    'bg-primary text-white items-center' => $routeName === 'communication-controls',
                                    'group hover:bg-slate-100 hover:text-slate-900 focus:bg-slate-100 focus:text-slate-900' => $routeName !== 'communication-controls',
                                ])
                            >
                                <x-lucide-sliders-horizontal
                                    @class([
                                        'size-5',
                                        'text-black delay-100 group-hover:text-black group-focus:text-slate-500' => $routeName !== 'communication-controls',
                                    ])
                                />
                                <span>{{ __('Communication Controls') }}</span>
                            </div>
                        </a>
                        <a
                            wire:navigate
                            href="{{ route('consumer.personalize_logo') }}"
                        >
                            <div
                                @class([
                                    'flex space-x-2 rounded-lg px-4 py-2.5 text-slate-700 ease-in-out delay-100 tracking-wide outline-none transition-all',
                                    'bg-primary text-white items-center' => $routeName === 'personalize-your-experience',
                                    'group hover:bg-slate-100 hover:text-slate-900 focus:bg-slate-100 focus:text-slate-900' => $routeName !== 'personalize-your-experience',
                                ])
                            >
                                <x-lucide-palette
                                    @class([
                                        'size-5',
                                        'text-black delay-100 group-hover:text-black group-focus:text-slate-500' => $routeName !== 'personalize-your-experience',
                                    ])
                                />
                                <span>{{ __('Personalize Your Experience') }}</span>
                            </div>
                        </a>
                    </ul>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-8">
                {{ $slot }}
            </div>
        </div>
    </main>
</div>
