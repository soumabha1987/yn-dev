<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendScheduleExportEmailJob;
use App\Mail\ScheduleExportMail;
use App\Models\ScheduleExport;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendScheduleExportEmailJobTest extends TestCase
{
    #[Test]
    public function can_send_email_with_attachment_of_file(): void
    {
        Mail::fake();

        $scheduleExport = ScheduleExport::factory()->create([
            'sftp_connection_id' => null,
            'emails' => [fake()->safeEmail()],
        ]);

        Storage::put($filename = 'test.json', json_encode(['test' => 'test user']));

        SendScheduleExportEmailJob::dispatch($scheduleExport, $filename);

        Mail::assertQueued(
            ScheduleExportMail::class,
            fn (ScheduleExportMail $mail) => $mail->hasTo($scheduleExport->emails[0]) &&
            $mail->hasAttachment(Attachment::fromStorage($filename)->as('test.json')->withMime('application/json'))
        );
    }
}
