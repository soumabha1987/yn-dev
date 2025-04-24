@use('Illuminate\Support\Facades\Storage')

<x-mail::layout>
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            <img src="{{ Storage::disk('public_assets')->url('images/app-logo.png') }}" width="200px">
        </x-mail::header>
    </x-slot:header>

## Hello {{ str($user->name)->title() }}!

We would like to inform you that your account associated with the following details has been deleted:

<ul>
<li><b>Name:</b> {{ str($user->name)->title() }}</li>
<li><b>Email:</b> {{ $user->email }}</li>
<li><b>Deletion Date and Time:</b> {{ $user->blocked_at->toDateTimestring() }}</li>
</ul>

If you believe this deletion was made in error or you have questions regarding this action, please contact our support team immediately.

You can reach us at <a href="mailto:help@younegotiate.com" target="_blank">help@younegotiate.com</a>

Thank you for your understanding.

<img src="{{ Storage::disk('public_assets')->url('images/app-logo.png') }}" width="100px">

    <x-slot:footer>
        <x-mail::footer>
            <p style="text-align: left">@lang('YouNegotiate is an exciting new app to help consumers start knocking out their collection debt in minutes from their phone or computer and say hello to a higher credit score!')</p>
            <p style="text-align: left">@lang('YouNegotiate was created by the consumers, for the consumers, to manage all of your collection accounts without ever having to pick up the phone or provide your credit card and/or bank account information to agencies or creditors again!')</p>
            <p style="text-align: left">@lang('From your personal YouNegotiate account You can QUICKLY and EASILY view your collection accounts, manage digital negotiations, send offers you can afford, and manage payment arrangements!')</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')</p>
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
