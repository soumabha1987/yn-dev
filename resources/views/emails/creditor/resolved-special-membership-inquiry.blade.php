@use('Illuminate\Support\Facades\Storage')

<x-mail::layout>
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            <img src="{{ Storage::disk('public_assets')->url('images/app-logo.png') }}" width="200px">
        </x-mail::header>
    </x-slot:header>

<h2>Hi {{ explode(' ', $membershipInquiry->company->owner_full_name)[0] }},</h2>

<p>We're happy to report our team created your custom membership plan!</p>

<p>Please log into your portal account to view.</p>
    &nbsp;

<p>Welcome to the community!.</p>
    &nbsp;

<h2>Cheers,</h2>
<h2>Your Rocking YouNegotiate Team.</h2>

    <x-slot:footer>
        <x-mail::footer>
            <p style="text-align: left">@lang('YouNegotiate is an exciting new app to help consumers start knocking out their collection debt in minutes from their phone or computer and say hello to a higher credit score!')</p>
            <p style="text-align: left">@lang('YouNegotiate was created by the consumers, for the consumers, to manage all of your collection accounts without ever having to pick up the phone or provide your credit card and/or bank account information to agencies or creditors again!')</p>
            <p style="text-align: left">@lang('From your personal YouNegotiate account You can QUICKLY and EASILY view your collection accounts, manage digital negotiations, send offers you can afford, and manage payment arrangements!')</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')</p>
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
