<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use App\Enums\CommunicationCode;
use App\Enums\ELetterType;
use App\Exports\CFPBConsumersExport;
use App\Jobs\ConsumersDownloadCFPBRegisterLetter;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Traits\WithPagination;
use App\Models\Consumer;
use App\Models\ELetter;
use App\Models\FileUploadHistory;
use App\Models\User;
use App\Services\CompanyMembershipService;
use App\Services\EcoLetterPaymentService;
use App\Services\FileUploadHistoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CFPBRegisterPage extends Component
{
    use WithPagination;

    public float $ecoMailAmount;

    public bool $withQrCode = true;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();

        $this->user->loadMissing('company');
    }

    public function mount(): void
    {
        $this->ecoMailAmount = app(CompanyMembershipService::class)->fetchELetterFee($this->user->company_id);
    }

    public function downloadUploadedFile(FileUploadHistory $fileUploadHistory): ?StreamedResponse
    {
        $fileUploadHistory->loadMissing('consumers.reason');

        $downloadFilename = now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv';

        $filename = 'download-report/cfpb-consumers/' . $downloadFilename;

        $consumers = $fileUploadHistory->consumers;

        Excel::store(
            new CFPBConsumersExport($consumers),
            $filename,
            writerType: ExcelExcel::CSV
        );

        return Storage::download($filename);
    }

    public function cfpbDisable(FileUploadHistory $fileUploadHistory): void
    {
        $fileUploadHistory->update(['cfpb_hidden' => true]);

        $this->success(__('List deleted.'));

        $this->dispatch('close-confirmation-box');
    }

    public function downloadLetters(FileUploadHistory $fileUploadHistory): ?StreamedResponse
    {
        $fileUploadHistory->loadMissing('activeConsumers.company');

        $consumersCount = $fileUploadHistory->activeConsumers->count();

        if ($consumersCount === 0) {
            $this->error(__('File is showing no consumer accounts. Please email help@Younegotiate.com to let them know your CFPB file is missing.'));

            $this->reset('withQrCode');

            $this->dispatch('close-confirmation-box');

            return null;
        }

        if ($consumersCount > 100) {
            ConsumersDownloadCFPBRegisterLetter::dispatch($fileUploadHistory, $this->withQrCode);

            $this->success(__("It takes a bit of time when  downloading 100 or more letters. We will email you the link as soon as it's ready!"));

            $this->reset('withQrCode');

            $this->dispatch('close-confirmation-box');

            return null;
        }

        $pdf = Pdf::setOption(['isRemoteEnabled' => true, 'enableAutoBreak' => false])
            ->loadView(
                'pdf.creditor.consumers-cfpb-pdf',
                ['consumers' => $fileUploadHistory->activeConsumers, 'withQrCode' => $this->withQrCode]
            )
            ->setPaper('A4')
            ->output();

        $this->success(__('Letters ready for download.'));

        $this->reset('withQrCode');

        $this->dispatch('close-confirmation-box');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf;
        }, 'you_negotiate_cfpb_consumers_letter.pdf');
    }

    public function secureEcoLetters(FileUploadHistory $fileUploadHistory): void
    {
        $fileUploadHistory->loadMissing('activeConsumers', 'company');

        $consumersCount = $fileUploadHistory->activeConsumers->count();

        if ($consumersCount === 0) {
            $this->error(__('Sorry, this file does not contain an active consumer.'));

            $this->reset('withQrCode');

            $this->dispatch('close-confirmation-box');

            return;
        }

        $isSuccessfullyDeduction = app(EcoLetterPaymentService::class)->applyEcoLetterDeduction($fileUploadHistory->company, $consumersCount);

        if (! $isSuccessfullyDeduction) {
            $this->reset('withQrCode');

            return;
        }

        $consumers = $fileUploadHistory->activeConsumers();

        $consumers->each(function (Consumer $consumer): void {
            TriggerEmailAndSmsServiceJob::dispatch($consumer, CommunicationCode::CFPB_ECO_MAIL);
        });

        $eLetter = ELetter::query()
            ->create([
                'company_id' => $fileUploadHistory->company_id,
                'subclient_id' => $fileUploadHistory->subclient_id,
                'type' => $this->withQrCode ? ELetterType::CFPB_WITH_QR : ELetterType::CFPB_WITHOUT_QR,
                'disabled' => false,
            ]);

        $eLetter->consumers()->attach($consumers->pluck('id')->all(), ['enabled' => true, 'read_by_consumer' => false]);

        $this->success(__('All secure EcoLetters have been successfully sent.'));

        $this->reset('withQrCode');
    }

    public function render(): View
    {
        $data = [
            'company_id' => $this->user->company_id,
            'per_page' => $this->perPage,
        ];

        return view('livewire.creditor.cfpb-register-page')
            ->with('cfpbFileUploadHistories', app(FileUploadHistoryService::class)->fetchOnlyCFPBReportType($data))
            ->title(__('CFPB Template'));
    }
}
