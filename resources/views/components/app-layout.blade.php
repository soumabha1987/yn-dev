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
            {{ strip_tags($title) . ' - ' }}
        @endisset
        {{ config('app.name') }}
    </title>

    @vite('resources/js/app.js')

    @livewireStyles
    @filamentStyles
</head>

<body
    x-data="loading"
    class="is-sidebar-open flex flex-col min-h-screen"
    x-cloak
>
    <x-alert-banner
        :message="__('We\'re under beta testing, we may reset data, you may experience minor issues and external services may be in sandbox mode')"
        color="blue"
        :close-button="false"
    />
    <div
        id="root"
        class="flex grow bg-white"
        x-cloak
    >
        <x-sidebar :title="$title ?? ''" />

        <section
            x-show="visible"
            class="relative place-items-center grid h-screen w-screen gap-4 bg-white"
            x-cloak
        >
            <div class="bg-primary size-48 absolute animate-ping rounded-full delay-5s shadow-xl"></div>
            <div class="bg-primary/50 size-32 absolute animate-ping rounded-full shadow-xl"></div>
            <div class="size-24 absolute animate-pulse rounded-full shadow-xl"></div>
            <img src="{{ asset('images/loader.png') }}" class="size-16">
        </section>

        <main
            class="main-content w-full p-4"
            x-show="! visible"
            x-transition:enter.duration.1500ms
        >
            {{ $slot }}
        </main>
    </div>

    <footer class="main-content mt-auto">
        <x-footer />
    </footer>


    <livewire:notifications />
    @livewireScriptConfig
    @filamentScripts
</body>

</html>
