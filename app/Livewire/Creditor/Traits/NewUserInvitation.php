<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Traits;

use App\Mail\UserInvitationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

trait NewUserInvitation
{
    public function sendInvitationMail(string $email): void
    {
        $inviteUrl = URL::temporarySignedRoute(
            'new-user-register',
            now()->addDay(),
            ['email' => $email]
        );

        Mail::to($email)->send(new UserInvitationMail($inviteUrl));
    }
}
