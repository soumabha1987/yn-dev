<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Dashboard\Stats;

use App\Models\User;
use App\Services\ConsumerService;
use App\Services\ScheduleTransactionService;
use App\Services\TransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    #[Computed]
    public function stats(): mixed
    {
        return Cache::remember(
            "stats-{$this->user->id}",
            now()->addMinutes(15),
            function (): array {
                $scheduleTransactionService = app(ScheduleTransactionService::class);

                $consumer = app(ConsumerService::class)->getCountsByCompany($this->user->company_id, $this->user->subclient_id);
                $scheduleTransaction = $scheduleTransactionService->getScheduledPaymentOfLastThirtyDays($this->user->company_id, $this->user->subclient_id);
                $failedTransaction = $scheduleTransactionService->getTotalOfFailedPayments($this->user->company_id, $this->user->subclient_id);
                $successTransaction = app(TransactionService::class)->getTotalOfSuccessfulPayments($this->user->company_id, $this->user->subclient_id);

                return [
                    'consumer' => [
                        'total_count' => $consumer->total_count,
                        'total_balance_count' => $consumer->total_balance_count,
                        'accepted_count' => $consumer->accepted_count,
                    ],
                    'transaction' => [
                        'successful_payments' => $successTransaction->successful_payments, // @phpstan-ignore-line
                    ],
                    'scheduleTransaction' => [
                        'scheduled_payments' => $scheduleTransaction->scheduled_payments,
                        'failed_payments' => $failedTransaction->failed_payments, // @phpstan-ignore-line
                    ],
                ];
            }
        );
    }

    public function render(): View
    {
        return view('livewire.creditor.dashboard.stats.index');
    }

    public function placeholder(): View
    {
        return view('components.dashboard.placeholder');
    }
}
