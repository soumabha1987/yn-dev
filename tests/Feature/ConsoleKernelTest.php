<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConsoleKernelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2024-01-01');

        ScheduleListCommand::resolveTerminalWidthUsing(fn () => 80);
    }

    #[Test]
    public function it_can_check_the_list_of_command_will_display(): void
    {
        $this->artisan(ScheduleListCommand::class)->assertSuccessful();
    }
}
