<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\CFPBRegisterLetterZipMail;
use App\Models\FileUploadHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ConsumersDownloadCFPBRegisterLetter implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected FileUploadHistory $fileUploadHistory,
        protected bool $withQrCode,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pdfDirectory = 'public/cfpb-consumer/pdfs/' . $this->fileUploadHistory->id . '/';

        Storage::makeDirectory($pdfDirectory);

        $this->fileUploadHistory
            ->activeConsumers()
            ->chunk(100, function (Collection $consumers, $index) use ($pdfDirectory): void {
                $pdfContent = Pdf::setOption(['isRemoteEnabled' => true, 'enableAutoBreak' => false])
                    ->loadView(
                        'pdf.creditor.consumers-cfpb-pdf',
                        ['consumers' => $consumers, 'withQrCode' => $this->withQrCode]
                    )
                    ->setPaper('A4')
                    ->output();

                $fileName = Str::random(10) . '_consumers_chunk_' . $index . '.pdf';
                Storage::put($pdfDirectory . $fileName, $pdfContent);
            });

        $files = Storage::files($pdfDirectory);

        $zipDirectory = 'cfpb-consumer/zips/';
        Storage::disk('storage_creditor_assets')->makeDirectory($zipDirectory);

        $zipFileName = today()->format('Y_m_d') . '_' . $this->fileUploadHistory->id . '_' . Str::random(10) . '.zip';
        $zipFilePath = Storage::disk('storage_creditor_assets')->path($zipDirectory . $zipFileName);

        $zip = new ZipArchive;

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach ($files as $file) {
                $zip->addFile(Storage::path($file), basename($file));
            }
            $zip->close();
        }

        Storage::delete($files);

        $company = $this->fileUploadHistory->company;

        $fileUrl = Storage::disk('storage_creditor_assets')->url($zipDirectory . $zipFileName);
        Mail::to($company->owner_email)->send(new CFPBRegisterLetterZipMail($company, $fileUrl));
    }
}
