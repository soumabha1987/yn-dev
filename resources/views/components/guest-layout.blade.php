<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name') }}">
    <meta name="twitter:description" content="YouNegotiate offers this easy-to-use service free of charge to all consumers in order to help resolve debts in the way that works best for each individual. No need to speak to a collector or creditor, ever again. Instead, just manage your account with us and get out of debt at your pace.">
    <meta name="twitter:image" content="{{ asset('images/twitter-card.png') }}">

    <link rel="icon" type="image/png" href="{{ asset('images/loader.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

    <title>
        @isset($title)
            {{ $title . ' - ' }}
        @endisset
        {{ config('app.name') }}
    </title>

    @if (str(request()->getHost())->contains('consumer.younegotiate'))
        @vite('resources/js/app-consumer.js')
    @else
        @vite('resources/js/app.js')
    @endif

    @livewireStyles
    @filamentStyles
</head>
<body x-data="loading">
    <section
        x-show="visible"
        class="relative place-items-center grid h-screen w-screen gap-4 z-50 bg-white"
        x-cloak
    >
        <div class="bg-primary size-48 absolute animate-ping rounded-full delay-5s shadow-xl"></div>
        <div class="bg-primary/50 size-32 absolute animate-ping rounded-full shadow-xl"></div>
        <div class="size-24 absolute animate-pulse rounded-full shadow-xl"></div>
        <img src="{{ asset('images/loader.png') }}" alt="loader" class="size-16">
    </section>

    <div
        class="min-h-screen flex flex-col grow bg-[#FBFBFB]"
        x-show="! visible"
        x-transition:enter.duration.1200ms
        x-cloak
    >
        <x-guest.sidebar />
        <x-guest.header />

        <div class="flex-grow lg:my-10 w-full lg:max-w-xl lg:mx-auto h-full bg-white lg:bg-transparent">
            <div class="flex flex-col justify-center">
                {{ $slot }}
            </div>
        </div>

        <footer class="w-full">
            <x-guest.footer />
        </footer>
    </div>

    <livewire:notifications />

    @livewireScriptConfig
    @filamentScripts
</body>
</html>
