<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\info;

class CreateTilledWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:tilled-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will create tilled webhook';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $response = Http::tilled(config('services.merchant.tilled_account'))
            ->post('webhook-endpoints', [
                'enabled_events' => ['*'],
                'description' => 'This webhook create for all events whenever our tilled actions called',
                'url' => route('tilled-webhook-listener'),
            ]);

        if ($response->created()) {
            $data = $response->json();

            $secret = $data['secret'];

            Log::channel('daily')->info('Tilled webhook created', $response->json());

            info("Save this secret key in your pocket <options=bold;fg=magenta>$secret</>");

            $output = Process::run("echo '$secret' | pbcopy");

            if ($output->successful()) {
                info('Rest assured, I\'ve already made a copy of it. Now, all you need to do is paste it into your `.env` file.');
            }

            if (App::isLocal()) {
                $envFileContent = File::get($envFile = base_path('.env'));

                $updateEnvironmentVariables = [
                    'TILLED_WEBHOOK_SECRET' => $secret,
                ];

                foreach ($updateEnvironmentVariables as $key => $variable) {
                    $envFileContent = preg_replace(sprintf('/^%s=(.*)/m', $key), sprintf('%s=%s', $key, $variable), $envFileContent);
                    $this->line(sprintf('Variable <comment>%s</comment> updated in .env file.', $key));
                }

                File::put($envFile, $envFileContent);
            }

            return Command::SUCCESS;
        }

        if ($response->failed()) {
            Log::channel('daily')->error('Creating tilled web hook failed', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);

            $this->error($response->body());

            return Command::FAILURE;
        }

        return Command::FAILURE;
    }
}
