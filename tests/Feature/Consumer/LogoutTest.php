<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use App\Livewire\Consumer\Logout;
use App\Models\Consumer;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    #[Test]
    public function it_can_render_logout_component(): void
    {
        Livewire::test(Logout::class)
            ->assertViewIs('livewire.consumer.logout')
            ->assertOk();
    }

    #[Test]
    public function it_can_perform_logout_when_submit_the_form(): void
    {
        Cache::shouldReceive('flush')->once()->withNoArgs()->andReturnTrue();

        $consumer = Consumer::factory()->create([
            'subclient_id' => null,
            'consumer_profile_id' => null,
        ]);

        Livewire::actingAs($consumer)
            ->test(Logout::class)
            ->call('logout')
            ->assertRedirect('/');

        Notification::assertNotified(__('Logged out. Have a great day and see you soon to knock out debt.'));
    }
}
