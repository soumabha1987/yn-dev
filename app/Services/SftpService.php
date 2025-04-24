<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ScheduleExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Throwable;

class SftpService
{
    public const FAKE_FILE_NAME = 'you-negotiate.txt';

    /**
     * @param  array{
     *      host: string,
     *      port: ?string,
     *      username:string,
     *      password: string,
     *      folder_path: ?string
     *  }  $data
     */
    public function validate(array $data): bool
    {
        try {
            $disk = Storage::createSftpDriver([
                'host' => $data['host'],
                'username' => $data['username'],
                'password' => $data['password'],
                'port' => (int) ($data['port'] ?? 22),
                'timeout' => 360,
            ]);

            $content = __('This file is create for testing by :app', ['app' => config('app.name')]);

            $filename = self::FAKE_FILE_NAME;

            if ($data['folder_path'] ?? false) {
                $filename = Str::of($data['folder_path'])
                    ->finish('/')
                    ->when(! str($data['folder_path'])->startsWith('/'), fn (Stringable $string) => $string->prepend('/'))
                    ->append($filename)
                    ->toString();
            }

            if ($disk->put($filename, $content)) {
                $disk->delete($filename);

                return true;
            }

            return false;
        } catch (Throwable $exception) {
            Log::channel('daily')->error('SFTP Credentials not valid', [
                'data' => $data,
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            return false;
        }
    }

    public function put(ScheduleExport $scheduleExport, string $filename): void
    {
        $sftpConnection = $scheduleExport->sftpConnection;

        try {
            $disk = Storage::createSftpDriver([
                'host' => $sftpConnection->host,
                'username' => $sftpConnection->username,
                'password' => $sftpConnection->password,
                'port' => (int) ($sftpConnection->port ?? 22),
                'timeout' => 360,
            ]);

            $dropFilename = $scheduleExport->frequency->filename($scheduleExport->report_type->value);

            $dropFilename = Str::of($sftpConnection->export_filepath)
                ->finish(DIRECTORY_SEPARATOR)
                ->unless(str($sftpConnection->export_filepath)->startsWith('/'), fn (Stringable $string) => $string->prepend('/'))
                ->append($dropFilename)
                ->toString();

            $disk->put($dropFilename, Storage::get($filename));
        } catch (Throwable $exception) {
            Log::channel('daily')->error('When put the file using sftp credentials', [
                'data' => $scheduleExport,
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);
        }
    }
}
