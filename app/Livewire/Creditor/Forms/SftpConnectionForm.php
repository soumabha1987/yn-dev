<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Models\SftpConnection;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SftpConnectionForm extends Form
{
    public string $name = '';

    public string $host = '';

    public int $port = 22;

    public string $username = '';

    public string $password = '';

    public string $used_for = 'export';

    public string $import_filepath = '';

    public string $export_filepath = '';

    public function init(SftpConnection $sftpConnection): void
    {
        $this->fill([
            'name' => $sftpConnection->name,
            'host' => $sftpConnection->host,
            'port' => $sftpConnection->port,
            'username' => $sftpConnection->username,
            'password' => $sftpConnection->password,
            'used_for' => match (true) {
                $sftpConnection->export_filepath !== null && $sftpConnection->import_filepath !== null => 'both',
                $sftpConnection->export_filepath !== null => 'export',
                $sftpConnection->import_filepath !== null => 'import',
                default => 'export',
            },
            'import_filepath' => filled($sftpConnection->import_filepath) ? $sftpConnection->import_filepath : '',
            'export_filepath' => filled($sftpConnection->export_filepath) ? $sftpConnection->export_filepath : '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['nullable', 'integer'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'used_for' => ['required', 'string', Rule::in(['import', 'export', 'both'])],
            'import_filepath' => [
                'nullable',
                Rule::requiredIf(fn (): bool => in_array($this->used_for, ['import', 'both'])),
                'string',
                'max:255',
            ],
            'export_filepath' => [
                'nullable',
                Rule::requiredIf(fn (): bool => in_array($this->used_for, ['export', 'both'])),
                'string',
                'max:255',
            ],
        ];
    }
}
