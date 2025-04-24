<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\DeletePartnerMonthlyBillingReportJob;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePartnerMonthlyBillingReportJobTest extends TestCase
{
    #[Test]
    public function it_can_delete_provided_file(): void
    {
        Storage::put('test.txt', 'Hey buddy!');

        dispatch_sync(new DeletePartnerMonthlyBillingReportJob('test.txt'));

        Storage::assertMissing('test.txt');
    }
}
