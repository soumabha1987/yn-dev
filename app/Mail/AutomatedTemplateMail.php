<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Consumer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use stdClass;

class AutomatedTemplateMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected Consumer $consumer,
        protected string $customSubject,
        protected string $content,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name', 'YouNegotiate')),
            subject: $this->customSubject,
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

        $consumerPersonalizedLogo = $this->consumer->consumerPersonalizedLogo ?? $this->consumer->company->personalizedLogo;

        $personalizedLogo = $consumerPersonalizedLogo ?: $personalizedLogo;

        return new Content(markdown: 'emails.creditor.automated-template', with: [
            'content' => $this->content,
            'logoUrl' => 'data:image/svg+xml;base64,' . base64_encode(view('components.logo-svg', ['personalizedLogo' => $personalizedLogo])->render()),
            'unsubscribeUrl' => URL::signedRoute(
                'consumer.unsubscribe-email',
                ['data' => encrypt([
                    'consumer_email' => $this->consumer->consumerProfile->email,
                    'company_id' => $this->consumer->company_id,
                    'consumer_id' => $this->consumer->id,
                ])]
            ),
        ]);
    }
}
