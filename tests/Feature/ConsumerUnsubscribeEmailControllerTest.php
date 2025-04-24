<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\ConsumerUnsubscribe;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConsumerUnsubscribeEmailControllerTest extends TestCase
{
    #[Test]
    public function it_can_create_unsubscribe_consumer(): void
    {
        $consumer = Consumer::factory()
            ->for(Company::factory())
            ->for(
                ConsumerProfile::factory()
                    ->create(['email_permission' => true])
            )
            ->create();

        $generateUrl = URL::signedRoute(
            'consumer.unsubscribe-email',
            ['data' => encrypt([
                'consumer_email' => $consumer->email1,
                'company_id' => $consumer->company_id,
                'consumer_id' => $consumer->id,
            ])]
        );

        $this->assertDatabaseCount(ConsumerUnsubscribe::class, 0);

        $response = $this->get($generateUrl);

        $response->assertRedirect(route('consumer.login'));

        $this->assertDatabaseHas(ConsumerUnsubscribe::class, [
            'company_id' => $consumer->company_id,
            'consumer_id' => $consumer->id,
            'email' => $consumer->email1,
        ])
            ->assertDatabaseCount(ConsumerUnsubscribe::class, 1);

        $this->assertFalse($consumer->consumerProfile->refresh()->email_permission);

        Notification::assertNotified(__('You have successfully unsubscribed from our emails.'));
    }

    #[Test]
    public function it_can_valid_url_and_abort_invalid_url(): void
    {
        $consumer = Consumer::factory()
            ->for(Company::factory())
            ->create();

        $generateUrl = URL::signedRoute(
            'consumer.unsubscribe-email',
            ['data' => encrypt([
                'consumer_email' => $consumer->email1,
                'company_id' => $consumer->company_id,
                'consumer_id' => $consumer->id,
            ])]
        );

        $response = $this->withoutVite()
            ->get($generateUrl . 'abc');

        $response->assertRedirect(route('consumer.login'));

        Notification::assertNotified(__('Invalid or expired link.'));
    }
}
