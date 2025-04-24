<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CommunicationStatus;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CommunicationStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:communication-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used for seeding default data into communication status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $jsonData = File::json('app/Console/Commands/communication_statuses.json');

        $communicationStatuses = collect($jsonData)
            ->map(fn (array $communicationStatus) => [
                ...$communicationStatus,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        try {
            Validator::validate(
                ['code' => $communicationStatuses->pluck('code')->toArray()],
                ['code' => ['array', Rule::unique(CommunicationStatus::class)]]
            );
        } catch (Exception $exception) {
            $this->error($exception->getMessage());

            return Command::FAILURE;
        }

        CommunicationStatus::query()->insert($communicationStatuses->toArray());

        $this->info('Communication statuses are seeded successfully.');

        return Command::SUCCESS;
    }
}
