<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\SftpConnection;

use App\Livewire\Creditor\Forms\SftpConnectionForm;
use App\Models\SftpConnection;
use App\Models\User;
use App\Services\SftpService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreatePage extends Component
{
    public SftpConnectionForm $form;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function save(): void
    {
        $validatedData = $this->form->validate();

        $isValidCredentials = $this->validateSftpCredentials($validatedData);

        if ($isValidCredentials) {
            SftpConnection::query()->create([
                'company_id' => $this->user->company_id,
                'name' => $validatedData['name'],
                'host' => $validatedData['host'],
                'port' => filled($validatedData['port']) ? $validatedData['port'] : 22,
                'username' => $validatedData['username'],
                'password' => $validatedData['password'],
                ...match (true) {
                    $validatedData['used_for'] === 'both' => [
                        'import_filepath' => $validatedData['import_filepath'],
                        'export_filepath' => $validatedData['export_filepath'],
                    ],
                    $validatedData['used_for'] === 'export' => [
                        'export_filepath' => $validatedData['export_filepath'],
                    ],
                    $validatedData['used_for'] === 'import' => [
                        'import_filepath' => $validatedData['import_filepath'],
                    ],
                    default => [],
                },
            ]);

            $this->success(__('SFTP connected.'));

            $this->redirectRoute('creditor.sftp', navigate: true);

            return;
        }

        $this->error(__('SFTP credentials not working. Please try again and email help@younegotiate.com if we can help.'));
    }

    private function validateSftpCredentials(array $validatedData): bool
    {
        $sftpService = app(SftpService::class);

        $validationParams = [
            'host' => $validatedData['host'],
            'port' => $validatedData['port'],
            'username' => $validatedData['username'],
            'password' => $validatedData['password'],
        ];

        return match ($validatedData['used_for']) {
            'both' => $sftpService->validate([...$validationParams, 'folder_path' => $validatedData['import_filepath']])
                && $sftpService->validate([...$validationParams, 'folder_path' => $validatedData['export_filepath']]),
            'import' => $sftpService->validate([...$validationParams, 'folder_path' => $validatedData['import_filepath']]),
            'export' => $sftpService->validate([...$validationParams, 'folder_path' => $validatedData['export_filepath']]),
            default => false,
        };
    }

    public function render(): View
    {
        return view('livewire.creditor.sftp-connection.create-page')
            ->title('Create SFTP Connection');
    }
}
