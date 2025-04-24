<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestartPaymentPlanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restart:payment-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart payment plan for hold consumer';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Consumer::query()
            ->withWhereHas('company', function (Builder|BelongsTo $query): void {
                $query->whereNull('deleted_at');
            })
            ->where('status', ConsumerStatus::HOLD)
            ->where('restart_date', today()->toDateString())
            ->update([
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'restart_date' => null,
                'hold_reason' => null,
            ]);
    }
}
