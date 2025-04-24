<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\CreateTilledWebhookCommand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateTilledWebhookCommandTest extends TestCase
{
    #[Test]
    public function create_tilled_webhook(): void
    {
        Process::fake();

        config(['services.merchant.tilled_account' => fake()->uuid()]);

        $secret = fake()->uuid();

        Http::fake(fn () => Http::response(['secret' => $secret], 201));

        $this->artisan(CreateTilledWebhookCommand::class)->assertOk();

        Process::assertRan("echo '$secret' | pbcopy");
    }

    #[Test]
    public function can_not_create_tilled_webhook(): void
    {
        config(['services.merchant.tilled_account' => fake()->uuid()]);

        Http::fake(fn () => Http::response(['message' => 'oops!!'], 403));

        $this->artisan(CreateTilledWebhookCommand::class)
            ->assertFailed()
            ->expectsOutput('{"message":"oops!!"}');
    }
}
