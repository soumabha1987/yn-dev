<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteCFPBLettersZipFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:cfpb-letters-zip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deleted old CFPB e letters zip file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $zipDirectory = 'public/cfpb-consumer/zips/';

        $pastDate = today()->subDays(3)->format('Y_m_d');

        $pattern = '/^(\d{4}_\d{2}_\d{2})_.*\.zip$/';

        $files = Storage::allFiles($zipDirectory);

        $oldFiles = collect($files)
            ->filter(fn (string $file) => preg_match($pattern, basename($file), $matches) && $matches[0] < $pastDate)
            ->all();

        Storage::delete($oldFiles);

        $this->info('Old zip files removed successfully.');

        Log::channel('daily')->info('Old zip files removed successfully.', ['deleted_files' => $oldFiles]);
    }
}
