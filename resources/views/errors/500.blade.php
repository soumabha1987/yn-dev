@auth('consumer')
    <x-consumer.app-layout :title="__('Error 500')">
        <x-errors.500
            :button-label="__('Back to Home')"
            :button-action="route('consumer.account')"
        />
    </x-consumer.app-layout>
@endauth

@auth('web')
    <x-app-layout :title="__('Error 500')">
        <x-errors.500
            :button-label="__('Back to Dashboard')"
            :button-action="route('home')"
        />
    </x-app-layout>
@endauth

@guest
    <x-guest-layout :title="__('Error 500')">
        <x-errors.500 />
    </x-guest-layout>
@endguest
