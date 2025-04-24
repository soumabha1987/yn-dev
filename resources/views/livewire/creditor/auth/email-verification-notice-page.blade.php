<div>
    <div
        wire:poll.keep-alive="$refresh"
        class="flex flex-col items-center"
    >
        <div class="flex flex-grow items-center justify-center w-full">
            <div class="my-6 w-full bg-white px-6 py-8 shadow-sm ring-1 ring-gray-950/5 sm:rounded-xl sm:px-6 sm:max-w-2xl">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-2xl text-primary">
                        {{ __('Verify Your Email Address') }}
                    </span>
                </div>

                <div class="grow py-8 text-base text-black">
                    <span class="text-lg font-medium">
                        {{ __('Welcome to :appName', ['appName' => config('app.name')]) . ', ' }}
                        <span class="font-bold text-primary">{{ auth()->user()->name }}.</span>
                    </span>
                    <p class="mt-4 line-clamp-3">
                        {{ __('Before proceeding, please check your email for a verification link.') }}
                    </p>
                    <p class="mt-4 line-clamp-3">
                        {{ __('If you haven\'t received the email or the link isn\'t working, please click below.') }}
                    </p>
                </div>

                <hr class="h-px bg-slate-200">

                <div class="w-full flex flex-wrap sm:flex-nowrap space-y-2 sm:space-y-0 sm:space-x-2 items-center mt-4">
                    <button
                        type="button"
                        wire:click="logout"
                        wire:target="logout"
                        wire:loading.attr="disabled"
                        class="btn disabled:opacity-50 bg-slate-150 w-full font-medium text-base text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                    >
                        <x-lucide-loader-2
                            wire:loading
                            wire:target="logout"
                            class="size-5 mr-2 animate-spin"
                        />
                        <x-lucide-log-out
                            wire:loading.remove
                            wire:target="logout"
                            class="size-5 mr-2"
                        />
                        {{ __('Logout') }}
                    </button>
                    <button
                        type="button"
                        wire:click="resendEmailVerification"
                        wire:target="resendEmailVerification"
                        wire:loading.attr="disabled"
                        class="btn disabled:opacity-50 select-none w-full text-base text-white bg-primary hover:bg-primary-focus focus:bg-primary-focus text-nowrap"
                    >
                        <x-lucide-loader-2
                            wire:loading
                            wire:target="resendEmailVerification"
                            class="size-4 mr-2 animate-spin"
                        />
                        <x-lucide-send
                            wire:loading.remove
                            wire:target="resendEmailVerification"
                            class="size-4 mr-2"
                        />
                        {{ __('Click here to request another') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
