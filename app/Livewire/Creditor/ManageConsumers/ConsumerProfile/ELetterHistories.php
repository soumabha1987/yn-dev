<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageConsumers\ConsumerProfile;

use App\Enums\ELetterType;
use App\Livewire\Traits\WithPagination;
use App\Models\Consumer;
use App\Models\ConsumerELetter;
use App\Services\ConsumerELetterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ELetterHistories extends Component
{
    use WithoutUrlPagination;
    use WithPagination;

    public Consumer $consumer;

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

        $this->success(__('CFPB Collection letter downloaded.'));

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf;
        }, 'you_negotiation_cfpb_letter.pdf');
    }

    public function render(): View
    {
        return view('livewire.creditor.manage-consumers.consumer-profile.e-letter-histories')
            ->with('eLetters', app(ConsumerELetterService::class)->fetchByConsumer($this->consumer->id, $this->perPage));
    }
}
