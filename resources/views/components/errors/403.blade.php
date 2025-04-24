@props([
    'buttonLabel' => __('Back To Login'),
    'buttonAction' => str(request()->host())->contains('consumer') ? route('consumer.login') : route('login'),
])

<main class="grid w-full grow grid-cols-1 place-items-center">
    <div class="max-w-md p-6 text-center">
        <div class="w-full">
            <img class="w-full" src="{{ asset('images/error-401-and-403.svg') }}" alt="image" />
        </div>
        <p class="pt-4 text-7xl font-bold text-primary">
            {{ response()->make()::HTTP_FORBIDDEN }}
        </p>
        <p class="pt-4 text-2xl font-semibold text-black">
            {{ __('You are not authorized') }}
        </p>
        <p class="pt-2 text-black/80">
            {{ __('You are missing the required rights to be able to access this page') }}
        </p>
        <a
            href="{{ $buttonAction }}"
            class="btn mt-8 h-11 bg-primary text-base font-medium text-white hover:bg-primary-focus hover:shadow-lg hover:shadow-primary/50 focus:bg-primary-focus focus:shadow-lg focus:shadow-primary/50 active:bg-primary-focus/90"
        >
            {{ $buttonLabel }}
        </a>
    </div>
</main>
