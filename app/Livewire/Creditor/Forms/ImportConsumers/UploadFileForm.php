<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\ImportConsumers;

use App\Models\CsvHeader;
use App\Rules\AddressSingleSpace;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class UploadFileForm extends Form
{
    public string $header_name = '';

    public ?UploadedFile $header_file;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'header_name' => [
                'required',
                'string',
                'max:160',
                new AddressSingleSpace,
                Rule::unique(CsvHeader::class, 'name')
                    ->where('company_id', Auth::user()->company_id),
            ],
            'header_file' => [
                'required',
                'file',
                function (string $attribute, mixed $value, Closure $fail) {
                    // TODO: Debug about why laravel is not allowing the mimetype checked!

                    if ($this->header_file->guessExtension() !== 'csv') {
                        return $fail(__('Invalid file format. Please upload a valid CSV file.'));
                    }

                    $stream = @fopen($this->header_file->path(), 'r');

                    if (is_bool($stream)) {
                        return $fail(__('This file can not open.'));
                    }

                    $uploadedFileHeaders = @fgetcsv($stream);

                    $headers = array_filter(array_map('trim', $uploadedFileHeaders));

                    if (blank($headers)) {
                        return $fail(__('The headers must not be empty.'));
                    }

                    @fclose($stream);
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'header_name.unique' => __('This header name already exists.'),
        ];
    }
}
