<div>
    <div class="flex flex-col items-center bg-gray-200 py-2">
        <div class="flex flex-row justify-center">
            <livewire:creditor.logo />
        </div>
        <div class="flex flex-row justify-center">
            <div class="my-1 justify-center text-xs text-primary">
                <x-terms-of-use-and-privacy-policy />
            </div>
        </div>
        <p class="mx-auto text-black">
            &copy; {{ date('Y') . ' ' . config('app.name') }} &trade; | {{ __('All rights reserved') }}
        </p>
    </div>
</div>
