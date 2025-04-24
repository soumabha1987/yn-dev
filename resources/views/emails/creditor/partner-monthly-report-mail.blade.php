@use('Illuminate\Support\Facades\Storage')

<x-mail::layout>
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            <img src="{{ Storage::disk('public_assets')->url('images/app-logo.png') }}" width="200px">
        </x-mail::header>
    </x-slot:header>

<h2>Hi, {{ $partner->name }}</h2>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;We hope this message finds you well. Please find attached the billing report for {{ today()->format('M') }}. This report includes a detailed summary of all transactions, payments, and any outstanding balances during the period.</p>

Thank you for your continued partnership. We value your business and look forward to serving you in the future.

Cheers to You

<img src="{{ Storage::disk('public_assets')->url('images/app-logo.png') }}" width="100px">

<a href="mailto:help@younegotiate.com">help@younegotiate.com</a>

    <x-slot:footer>
        <x-mail::footer>
            <p style="text-align: left">@lang('YouNegotiate is an exciting new app to help consumers start knocking out their collection debt in minutes from their phone or computer and say hello to a higher credit score!')</p>
            <p style="text-align: left">@lang('YouNegotiate was created by the consumers, for the consumers, to manage all of your collection accounts without ever having to pick up the phone or provide your credit card and/or bank account information to agencies or creditors again!')</p>
            <p style="text-align: left">@lang('From your personal YouNegotiate account You can QUICKLY and EASILY view your collection accounts, manage digital negotiations, send offers you can afford, and manage payment arrangements!')</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')</p>
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
