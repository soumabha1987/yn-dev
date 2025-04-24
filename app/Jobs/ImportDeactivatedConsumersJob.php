<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CommunicationCode;
use App\Models\Consumer;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportDeactivatedConsumersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public array $deactivatedConsumerIds) {}

    public function handle(): void
    {
        foreach ($this->deactivatedConsumerIds as $consumerId) {
            $consumer = Consumer::query()
                ->where('id', $consumerId)
                ->whereDoesntHave('unsubscribe')
                ->first();

            if (filled($consumer)) {
                try {
                    $consumer->loadMissing(['consumerProfile', 'subclient', 'company']);

                    TriggerEmailAndSmsServiceJob::dispatch($consumer, CommunicationCode::CREDITOR_REMOVED_ACCOUNT);

                } catch (Exception $exception) {
                    Log::channel('import_consumers')->error('While sending an email to consumer', [
                        'consumer_id' => $consumer->id,
                        'message' => $exception->getMessage(),
                        'stack trace' => $exception->getTrace(),
                    ]);
                }
            }
        }
    }
}
