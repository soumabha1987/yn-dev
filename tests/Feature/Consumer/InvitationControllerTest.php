<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use App\Models\Consumer;
use App\Services\EncryptDecryptService;
use Filament\Notifications\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvitationControllerTest extends TestCase
{
    #[Test]
    public function it_can_required_the_key_for_invitation(): void
    {
        $this->get(route('webview'))->assertRedirectToRoute('consumer.login');

        Notification::assertNotified(__('Unauthorized login!'));
    }

    #[Test]
    public function it_can_redirect_if_consumer_not_found(): void
    {
        config(['services.yng.key' => 'test']);

        $search = app(EncryptDecryptService::class)
            ->encrypt('test', config('services.yng.key'));

        $this->get(route('webview', ['search' => $search]))
            ->assertRedirectToRoute('consumer.login');

        Notification::assertNotified(__('Unauthorized login!'));
    }

    #[Test]
    public function it_can_login_using_invitation(): void
    {
        $consumer = Consumer::factory()->create();

        config(['services.yng.key' => 'test']);

        $search = app(EncryptDecryptService::class)
            ->encrypt((string) $consumer->id, config('services.yng.key'));

        $this->get(route('webview', ['search' => $search]))
            ->assertSessionHas('required_ssn_verification')
            ->assertRedirectToRoute('consumer.verify_ssn');

        $this->assertAuthenticatedAs($consumer, 'consumer');
    }
}
