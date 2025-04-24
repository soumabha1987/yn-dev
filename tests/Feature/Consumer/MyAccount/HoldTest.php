<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\MyAccount;

use App\Enums\ConsumerStatus;
use App\Livewire\Consumer\MyAccount\Hold;
use App\Models\Consumer;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HoldTest extends TestCase
{
    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()->create(['status' => ConsumerStatus::PAYMENT_ACCEPTED]);

        $this->withoutVite()->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view_and_data(): void
    {
        Livewire::test(Hold::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.my-account.hold')
            ->assertViewHas('consumer', fn (Consumer $consumer): bool => $this->consumer->is($consumer))
            ->assertSet('form.restart_date', today()->addDay()->toDateString())
            ->assertSee(__('Submit'))
            ->assertDontSee(__('Update Restart Date'))
            ->assertDontSee(__('Restart Plan Now'));
    }

    #[Test]
    public function it_can_call_put_account_on_hold(): void
    {
        Livewire::test(Hold::class, ['consumer' => $this->consumer])
            ->set('form.restart_date', $restartDate = today()->addDays(fake()->numberBetween(2, 100))->toDateString())
            ->set('form.hold_reason', $holdReason = fake()->text())
            ->call('hold', $this->consumer)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog');

        Notification::assertNotified(__('Your account plan has been successfully placed on hold.'));

        $this->assertEquals(ConsumerStatus::HOLD, $this->consumer->refresh()->status);
        $this->assertEquals($restartDate, $this->consumer->restart_date->toDateString());
        $this->assertEquals($holdReason, $this->consumer->hold_reason);
    }

    #[Test]
    public function it_can_call_update_restart_date(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::HOLD,
            'restart_date' => $restartDate = today()->addMonth()->toDateString(),
            'hold_reason' => $holdReason = fake()->text(),
        ]);

        Livewire::test(Hold::class, ['consumer' => $this->consumer])
            ->assertSet('form.restart_date', $restartDate)
            ->assertSet('form.hold_reason', $holdReason)
            ->set('form.restart_date', $updatedRestartDate = today()->addDays(fake()->numberBetween(31, 100))->toDateString())
            ->set('form.hold_reason', $updatedHoldReason = fake()->text())
            ->call('hold', $this->consumer)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog');

        Notification::assertNotified(__('Your account plan has been successfully placed on hold.'));

        $this->assertEquals(ConsumerStatus::HOLD, $this->consumer->refresh()->status);
        $this->assertEquals($updatedRestartDate, $this->consumer->restart_date->toDateString());
        $this->assertEquals($updatedHoldReason, $this->consumer->hold_reason);
    }

    #[Test]
    #[DataProvider('requestValidation')]
    public function it_can_validation_errors_for_call_on_hold(array $requestSetData, array $requestErrors): void
    {
        Livewire::test(Hold::class, ['consumer' => $this->consumer])
            ->set($requestSetData)
            ->call('hold', $this->consumer)
            ->assertOk()
            ->assertHasErrors($requestErrors)
            ->assertNotDispatched('close-dialog');
    }

    public static function requestValidation(): array
    {
        return [
            [
                [
                    'form.restart_date' => '',
                ],
                [
                    'form.restart_date' => ['required'],
                    'form.hold_reason' => ['required'],
                ],
            ],
            [
                [
                    'form.restart_date' => str('a')->repeat(300),
                    'form.hold_reason' => str('a')->repeat(300),
                ],
                [
                    'form.restart_date' => ['date'],
                    'form.hold_reason' => ['max:255'],
                ],
            ],
            [
                [
                    'form.restart_date' => today()->toDateString(),
                    'form.hold_reason' => str('a')->repeat(250),
                ],
                [
                    'form.restart_date' => ['after:today'],
                ],
            ],
            [
                [
                    'form.restart_date' => today()->addDay()->format('m/d/Y'),
                ],
                [
                    'form.restart_date' => ['date_format:Y-m-d'],
                ],
            ],
        ];
    }
}
