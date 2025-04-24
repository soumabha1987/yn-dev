<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Enums\ELetterType;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\Consumer;
use App\Models\ConsumerELetter;
use App\Services\ConsumerELetterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.consumer.app-layout')]
class EcoMailBox extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    public bool $only_read_by_consumer = false;

    public int $unreadCount = 0;

    protected ConsumerELetterService $consumerELetterService;

    private Consumer $consumer;

    public function __construct()
    {
        $this->sortCol = 'created-at';
        $this->sortAsc = false;
        $this->consumer = Auth::guard('consumer')->user();
        $this->consumerELetterService = app(ConsumerELetterService::class);
    }

    public function mount(): void
    {
        $this->unreadCount = $this->consumerELetterService->unreadCount($this->consumer);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(ConsumerELetter $consumerELetter): void
    {
        $eLetter = $consumerELetter->eLetter;

        if (! $consumerELetter->read_by_consumer) {
            $this->dispatch('update-unread-email-count', --$this->unreadCount);
        }

        $consumerELetter->delete();

        if ($eLetter->consumerELetters->count() === 0) {
            $eLetter->delete();
        }

        $this->success(__('eLetter deleted.'));

        $this->dispatch('close-confirmation-box');
    }

    public function readByConsumer(ConsumerELetter $consumerELetter): void
    {
        if (! $consumerELetter->read_by_consumer) {
            $consumerELetter->update(['read_by_consumer' => true]);

            $this->dispatch('update-unread-email-count', --$this->unreadCount);
        }
    }

    public function downloadCFPBLetter(ConsumerELetter $consumerELetter): StreamedResponse
    {
        $consumerELetter->loadMissing('consumer.company', 'eLetter');

        $pdf = Pdf::setOption(['isRemoteEnabled' => true, 'enableAutoBreak' => false])
            ->loadView(
                'pdf.creditor.consumers-cfpb-pdf',
                ['consumers' => [$consumerELetter->consumer], 'withQrCode' => $consumerELetter->eLetter->type === ELetterType::CFPB_WITH_QR]
            )
            ->setPaper('A4')
            ->output();

        if (! $consumerELetter->read_by_consumer) {
            $consumerELetter->update(['read_by_consumer' => true]);

            $this->dispatch('update-unread-email-count', --$this->unreadCount);
        }

        $this->success(__('eLetter downloaded.'));

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf;
        }, 'you_negotiation_cfpb_letter.pdf');
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'created-at' => 'created_at',
            'company-name' => 'company_name',
            'account-offer' => 'account_offer',
            default => 'created_at',
        };

        $consumerELetters = $this->consumerELetterService->fetch([
            'consumer' => $this->consumer,
            'search' => $this->search,
            'only_read_by_consumer' => $this->only_read_by_consumer,
            'per_page' => $this->perPage,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
        ]);

        return view('livewire.consumer.eco-mail-box')
            ->with('consumerELetters', $consumerELetters)
            ->title(__('E-Letters'));
    }
}
