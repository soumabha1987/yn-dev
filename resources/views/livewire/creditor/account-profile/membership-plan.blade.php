@use('Illuminate\Support\Number')

<div>
    <x-account-profile.card
        :cardTitle="__('Membership Plans')"
        wire:submit="purchaseMembership"
    >
        <x-slot name="actionButtons">
            <button
                type="button"
                wire:click="$dispatchTo('creditor.account-profile.index-page', 'previous')"
                class="btn space-x-2 select-none bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
            >
                <x-heroicon-o-arrow-long-left class="size-5"/>
                <span>{{ __('Previous') }}</span>
            </button>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="store"
                class="btn space-x-2 disabled:opacity-50 bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
            >
                <span>{{ __('Save & Next') }}</span>
                <x-lucide-loader-2
                    wire:loading
                    wire:target="purchaseMembership"
                    class="size-5 animate-spin"
                />
                <x-heroicon-o-arrow-long-right
                    wire:loading.remove
                    wire:target="purchaseMembership"
                    class="size-5"
                />
            </button>
        </x-slot>

        <div class="space-y-4 mt-3">
            @error('selectedMembership')
                <span class="text-error mt-2">{{ __('Please select the membership before proceed.') }}</span>
            @enderror

            <div class="my-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                    @foreach ($memberships as $key => $membership)
                        <div
                            x-on:click="$wire.selectedMembership = {{ $membership->id }}"
                            wire:target="purchaseMembership"
                            wire:loading.class="opacity-50"
                            class="rounded-xl cursor-pointer border border-slate-400"
                            x-bind:class="$wire.selectedMembership === {{ $membership->id }} && 'outline outline-primary'"
                        >
                            <div class="flex flex-col justify-between h-full rounded-xl bg-slate-50 p-4 text-center">
                                <div class="mt-4">
                                    <h4 class="text-xl font-semibold text-slate-700">
                                        {{ $membership->name }}
                                    </h4>
                                    <span
                                        x-tooltip.placement.bottom="'{{ $membership->description }}'"
                                        class="mt-2 line-clamp-2 hover:underline hover:cursor-pointer"
                                    >
                                        {{ $membership->description }}
                                    </span>
                                </div>
                                <div class="mt-3 flex justify-center items-center">
                                    <span class="text-3xl tracking-tight text-primary">
                                        {{ Number::currency((float) $membership->price) }}
                                    </span> &nbsp;/ {{ $membership->frequency->displayName() }}
                                </div>
                                <div class="mt-3 space-y-1 text-left">
                                    <div class="flex items-start space-x-reverse">
                                        <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                            <x-heroicon-m-check class="size-5 text-success" />
                                        </div>
                                        <span class="font-medium text-black">
                                            {{ __('Upload account limit :accounts', ['accounts' => $membership->upload_accounts_limit]) }}
                                        </span>
                                    </div>
                                    <div class="flex items-start space-x-reverse">
                                        <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                            <x-heroicon-m-check class="size-5 text-success" />
                                        </div>
                                        <span class="font-medium text-black">
                                            {{ __(':fees fee on all consumer payments', ['fees' => Number::percentage($membership->fee, 2)]) }}
                                        </span>
                                    </div>
                                    @foreach ($membership->enableFeatures as $name => $value)
                                        <div class="flex items-start space-x-reverse">
                                            <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                                <x-heroicon-m-check class="size-5 text-success" />
                                            </div>
                                            <span class="font-medium text-black">
                                                {{ $value }}
                                            </span>
                                        </div>
                                    @endforeach
                                    @foreach ($membership->disableFeatures as $name => $value)
                                        <div class="flex items-start space-x-reverse">
                                            <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                                <x-heroicon-m-x-mark class="size-5 text-error" />
                                            </div>
                                            <span class="font-medium">
                                                {{ $value }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    <button
                                        type="button"
                                        wire:click="purchaseMembership({{ $membership->id }})"
                                        class="btn inline-flex items-center space-x-1 rounded-full font-medium text-white bg-primary hover:bg-primary-400"
                                        wire:target="purchaseMembership({{ $membership->id }})"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="disabled:opacity-50"
                                    >
                                        <x-lucide-loader-2
                                            wire:loading
                                            wire:target="purchaseMembership({{ $membership->id }})"
                                            class="size-5 animate-spin mr-1"
                                        />
                                        {{ __('Choose Plan') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <livewire:creditor.membership-inquiries.card />
                </div>
            </div>
        </div>
    </x-account-profile.card>
</div>
