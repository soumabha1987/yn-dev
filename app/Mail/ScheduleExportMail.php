<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ScheduleExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use stdClass;

class ScheduleExportMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected ScheduleExport $scheduleExport,
        protected string $filename,
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $frequency = $this->scheduleExport->frequency->displayName();

        $reportType = $this->scheduleExport->report_type->displayName();

        return new Envelope(subject: "Schedule $frequency Report of $reportType");
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $personalizedLogo = new stdClass;
        $personalizedLogo->primary_color = '#3279be';
        $personalizedLogo->secondary_color = '#000000';

        return new Content(markdown: 'emails.creditor.schedule-export', with: [
            'scheduleExport' => $this->scheduleExport,
            'logoUrl' => 'data:image/svg+xml;base64,' . base64_encode(view('components.logo-svg', ['personalizedLogo' => $personalizedLogo])->render()),
        ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorage($this->filename),
        ];
    }
}
