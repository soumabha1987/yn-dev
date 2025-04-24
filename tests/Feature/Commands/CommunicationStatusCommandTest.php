<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\CommunicationStatusCommand;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Models\CommunicationStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommunicationStatusCommandTest extends TestCase
{
    #[Test]
    public function it_can_throw_validation_error_when_you_have_already_seeded_communication_statuses(): void
    {
        CommunicationStatus::factory()->create([
            'code' => CommunicationCode::WELCOME,
            'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
        ]);

        $this->artisan(CommunicationStatusCommand::class)
            ->assertFailed()
            ->assertExitCode(Command::FAILURE)
            ->expectsOutput(__('validation.unique', ['attribute' => 'code']));
    }

    #[Test]
    public function it_can_seed_the_records(): void
    {
        CommunicationStatus::query()->delete();

        $jsonData = File::json('app/Console/Commands/communication_statuses.json');

        $this->artisan(CommunicationStatusCommand::class)
            ->assertOk()
            ->assertExitCode(Command::SUCCESS)
            ->expectsOutput('Communication statuses are seeded successfully.');

        $this->assertDatabaseCount(CommunicationStatus::class, count($jsonData));
    }
}
