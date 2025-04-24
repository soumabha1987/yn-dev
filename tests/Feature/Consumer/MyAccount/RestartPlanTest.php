<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\MyAccount;

use App\Enums\ConsumerStatus;
use App\Livewire\Consumer\MyAccount\RestartPlan;
use App\Models\Consumer;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestartPlanTest extends TestCase
{
    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->create([
                'status' => ConsumerStatus::HOLD,
                'restart_date' => fake()->date(),
                'hold_reason' => fake()->text(20),
            ]);

        $this->withoutVite()->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view_and_data(): void
    {
        Livewire::test(RestartPlan::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.my-account.restart-plan')
            ->assertViewHas('consumer', fn (Consumer $consumer): bool => $this->consumer->is($consumer))
            ->assertSee(__('Restart Plan Now'))
            ->assertSee(__('This will restart the payment plan immediately and remove the hold.'));
    }

    #[Test]
    public function it_can_call_restart_plan(): void
    {
        Livewire::test(RestartPlan::class, ['consumer' => $this->consumer])
            ->call('restartPlan')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog');

        Notification::assertNotified(__('Your account plan has been successfully restarted.'));

        $this->assertNull($this->consumer->refresh()->restart_date);
        $this->assertNull($this->consumer->hold_reason);
        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $this->consumer->refresh()->status);
    }
}
