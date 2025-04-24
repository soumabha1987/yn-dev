<div>
    <form
        wire:submit="logout"
        autocomplete="off"
    >
        <button
            type="submit"
            class="btn h-9 w-full disabled:opacity-50 bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
            wire:target="logout"
            wire:loading.attr="disabled"
        >
            <span wire:target="logout" wire:loading.remove>{{ __('Logout') }}</span>
            <span wire:target="logout" wire:loading>{{ __('Logging out...') }}</span>
        </button>
    </form>
</div>
