<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ConsumerLogs;

use App\Livewire\Traits\WithPagination;
use App\Models\Consumer;
use App\Services\ConsumerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Url;
use Livewire\Component;

class Page extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $sessionLink = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function inspectlet(Consumer $consumer): void
    {
        $response = Http::withBasicAuth(config('services.inspectlet.username'), config('services.inspectlet.token'))
            ->post('https://api.inspectlet.com/v1/websites/' . config('services.inspectlet.user_id') . '/sessions', [
                'search' => json_encode([
                    'displayname' => $consumer->last_name . '_' . $consumer->dob->toDateString() . '_' . $consumer->last4ssn,
                ], JSON_PRETTY_PRINT),
            ]);

        $this->sessionLink = $response->json('response.sessions.0.sessionlink', '');

        if ($this->sessionLink === '') {
            $this->error(__('No video session found for this consumer.'));
        }
    }

    public function render(): View
    {
        $data = [
            'user' => Auth::user(),
            'per_page' => $this->perPage,
            'search' => $this->search,
        ];

        return view('livewire.creditor.consumer-logs.page')
            ->with('consumers', app(ConsumerService::class)->fetchWithConsumerActivity($data))
            ->title(__('Consumer Logs'));
    }
}
