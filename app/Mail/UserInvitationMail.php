<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use stdClass;

class UserInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $inviteUrl
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to YouNegotiate - Set Up Your Password',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $personalizedLogo = new stdClass;
        $personalizedLogo->primary_color = '#3279be';
        $personalizedLogo->secondary_color = '#000000';

        return new Content(markdown: 'emails.creditor.user-invitation-mail', with: [
            'logoUrl' => 'data:image/svg+xml;base64,' . base64_encode(view('components.logo-svg', ['personalizedLogo' => $personalizedLogo])->render()),
            'markdownLogoUrl' => 'data:image/svg+xml;base64,' . base64_encode(view('components.mail-markdown-logo-svg', ['personalizedLogo' => $personalizedLogo])->render()),
        ]);
    }
}
