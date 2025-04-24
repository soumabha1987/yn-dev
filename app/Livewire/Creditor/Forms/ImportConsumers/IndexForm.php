<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\ImportConsumers;

use App\Enums\FileUploadHistoryType;
use App\Models\CsvHeader;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class IndexForm extends Form
{
    public int $header;

    public string $import_type = '';

    public ?UploadedFile $import_file;

    public function rules(): array
    {
        return [
            'header' => [
                'required',
                'integer',
                Rule::exists(CsvHeader::class, 'id')
                    ->where('is_mapped', true)
                    ->where('company_id', Auth::user()->company_id),
            ],
            'import_type' => ['required', 'string', Rule::in(FileUploadHistoryType::values())],
            'import_file' => [
                'required',
                'file',
                function (string $attribute, mixed $value, Closure $fail) {
                    // TODO: Debug about why laravel is not allowing the mimetype checked!
                    $extension = pathinfo($this->import_file->getClientOriginalName(), PATHINFO_EXTENSION);

                    $allowedMimeTypes = ['csv'];

                    if (! in_array($extension, $allowedMimeTypes)) {
                        return $fail(__('validation.mimes', ['attribute' => 'uploaded file', 'values' => implode(',', $allowedMimeTypes)]));
                    }
                },
            ],
        ];
    }
}
