<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">

    <link rel="icon" type="image/png" href="{{ asset('images/loader.png') }}" />

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

    <title>
        @isset($title) {{ $title }} - @endisset
        {{ config('app.name') }}
    </title>

    @vite('resources/js/app-consumer.js')

    @livewireStyles
    @filamentStyles
</head>

<body
    x-data="loading"
    x-cloak
    class="is-header-blur navigation:horizontal"
>
    <div
        id="root"
        class="min-h-screen flex flex-col grow bg-[#FBFBFB]"
        x-cloak
    >
        <x-consumer.sidebar />

        <x-consumer.header />

        <section
            x-show="visible"
            x-cloak
            class="relative place-items-center grid h-screen w-screen gap-4 z-50 bg-white"
        >
            <div class="bg-primary size-48 absolute animate-ping rounded-full delay-5s shadow-xl"></div>
            <div class="bg-primary/50 size-32 absolute animate-ping rounded-full shadow-xl"></div>
            <div class="size-24 absolute animate-pulse rounded-full shadow-xl"></div>
            <img src="{{ asset('images/loader.png') }}" alt="loader" class="size-16">
        </section>
        <div
            x-show="! visible"
            x-transition:enter.duration.1200ms
            class="container mx-auto grow mt-2 md:mt-10"
            x-cloak
        >
            {{ $slot }}
        </div>

        <footer class="w-full mt-10">
            <x-consumer.footer />
        </footer>
    </div>

    <livewire:notifications />
    @livewireScriptConfig
    @filamentScripts
</body>
</html>
