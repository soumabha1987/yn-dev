<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;
use Illuminate\Support\Str;

enum FileUploadHistoryType: string
{
    use Values;

    case ADD = 'add';
    case ADD_ACCOUNT_WITH_CREATE_CFPB = 'add_account_with_create_cfpb';
    case DELETE = 'delete';
    case UPDATE = 'update';

    public function displayMessage(): string
    {
        return match ($this) {
            self::ADD => __('Add New Accounts'),
            self::ADD_ACCOUNT_WITH_CREATE_CFPB => __('Add New Accounts + Generate CFPB B1 Validation Letter'),
            self::UPDATE => __('Update Pay Terms, Email and/or Mobile'),
            self::DELETE => __('Delete Accounts'),
        };
    }

    public function fileHistoriesDisplayMessage(): string
    {
        return match ($this) {
            self::ADD => 'Add',
            self::ADD_ACCOUNT_WITH_CREATE_CFPB => 'Add+CFPB',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function getSftpImportFileNames(string $importFilepath, string $headerProfileName): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [
                $case->value => Str::of($importFilepath)
                    ->finish(DIRECTORY_SEPARATOR)
                    ->append($headerProfileName, DIRECTORY_SEPARATOR, $case->displayMessage())
                    ->toString(),
            ])
            ->all();
    }
}
