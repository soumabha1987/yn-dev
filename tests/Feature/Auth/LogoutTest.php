<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Livewire\Creditor\Auth\Logout;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    #[Test]
    public function it_can_perform_logout_when_submit_the_form(): void
    {
        $user = User::factory()->create();

        Cache::shouldReceive('flush')->once()->withNoArgs()->andReturnTrue();

        Livewire::actingAs($user)
            ->test(Logout::class)
            ->call('logout')
            ->assertRedirect();

        $this->assertGuest();

        Notification::assertNotified(__('Logged out. Have a great day and see you soon to knock out debt.'));
    }
}
