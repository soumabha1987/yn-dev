<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendPartnerMonthlyBillingReportJob;
use App\Mail\PartnerMonthlyReportsMail;
use App\Models\Partner;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendPartnerMonthlyBillingReportJobTest extends TestCase
{
    #[Test]
    public function it_can_send_monthly_report_to_the_partner(): void
    {
        Mail::fake();

        $partner = Partner::factory()->create();

        Storage::put('test.txt', 'Hey there!');

        dispatch_sync(new SendPartnerMonthlyBillingReportJob($partner, 'test.txt'));

        Mail::assertQueued(
            PartnerMonthlyReportsMail::class,
            fn (PartnerMonthlyReportsMail $mail) => $mail
                ->assertTo($partner->report_emails)
                ->assertHasBcc([new Address(config('mail.yn_bcc.address'), config('mail.yn_bcc.name', 'YouNegotiate'))])
        );
    }
}
