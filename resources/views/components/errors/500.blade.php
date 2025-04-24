@props([
    'buttonLabel' => __('Back To Login'),
    'buttonAction' => str(request()->host())->contains('consumer') ? route('consumer.login') : route('login'),
])

<main class="grid w-full grow grid-cols-1 place-items-center">
    <div class="max-w-md p-6 text-center">
        <div class="w-full">
            <img class="w-full" src="{{ asset('images/error-500.svg') }}" alt="image" />
        </div>
        <p class="pt-4 text-7xl font-bold text-primary">
            {{ response()->make()::HTTP_INTERNAL_SERVER_ERROR }}
        </p>
        <p class="pt-4 text-2xl font-semibold text-black">
            {{ __('Internal Server Error') }}
        </p>
        <p class="pt-2 text-black/80">
            {{ __('The server has been deserted for a while. Please be patient or try again later') }}
        </p>
        <a
            href="{{ $buttonAction }}"
            class="btn mt-8 h-11 bg-primary text-base font-medium text-white hover:bg-primary-focus hover:shadow-lg hover:shadow-primary/50 focus:bg-primary-focus focus:shadow-lg focus:shadow-primary/50 active:bg-primary-focus/90"
        >
            {{ $buttonLabel }}
        </a>
    </div>
</main>
