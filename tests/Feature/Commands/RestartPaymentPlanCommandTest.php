<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\RestartPaymentPlanCommand;
use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestartPaymentPlanCommandTest extends TestCase
{
    #[Test]
    public function it_can_today_restart_payment_plan(): void
    {
        $consumer = Consumer::factory()->create([
            'status' => ConsumerStatus::HOLD,
            'restart_date' => today()->toDateString(),
            'hold_reason' => fake()->text(),
        ]);

        $this->artisan(RestartPaymentPlanCommand::class)->assertOk();

        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $consumer->refresh()->status);
        $this->assertNull($consumer->restart_date);
        $this->assertNull($consumer->hold_reason);
    }

    #[Test]
    public function it_can_tomorrow_date_restart_payment_plan(): void
    {
        $consumer = Consumer::factory()->create([
            'status' => ConsumerStatus::HOLD,
            'restart_date' => today()->addDay()->toDateString(),
            'hold_reason' => fake()->text(),
        ]);

        $this->artisan(RestartPaymentPlanCommand::class)->assertOk();

        $this->assertEquals(ConsumerStatus::HOLD, $consumer->refresh()->status);
        $this->assertNotNull($consumer->restart_date);
        $this->assertNotNull($consumer->hold_reason);
    }

    #[Test]
    public function it_can_without_hold_status_restart_payment_plan(): void
    {
        $consumer = Consumer::factory()->create([
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
            'restart_date' => today()->toDateString(),
            'hold_reason' => fake()->text(),
        ]);

        $this->artisan(RestartPaymentPlanCommand::class)->assertOk();

        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $consumer->refresh()->status);
        $this->assertNotNull($consumer->restart_date);
        $this->assertNotNull($consumer->hold_reason);
    }
}
