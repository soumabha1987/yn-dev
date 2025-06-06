<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use stdClass;

class SetUpWizardStepsCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(protected Company $company)
    {
        $this->company->loadMissing('personalizedLogo');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Good job!, You have completed your setup'),
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

        $companyPersonalizedLogo = $this->company->personalizedLogo;

        $personalizedLogo = $companyPersonalizedLogo ?: $personalizedLogo;

        return new Content(markdown: 'emails.creditor.setup-wizard-completed-mail', with: [
            'logoUrl' => 'data:image/svg+xml;base64,' . base64_encode(view('components.logo-svg', ['personalizedLogo' => $personalizedLogo])->render()),
            'markdownLogoUrl' => 'data:image/svg+xml;base64,' . base64_encode(view('components.mail-markdown-logo-svg', ['personalizedLogo' => $personalizedLogo])->render()),
        ]);
    }
}
