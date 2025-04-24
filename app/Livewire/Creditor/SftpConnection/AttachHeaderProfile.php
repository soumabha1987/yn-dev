<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\SftpConnection;

use App\Models\CsvHeader;
use App\Models\User;
use App\Services\CsvHeaderService;
use App\Services\SftpConnectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class AttachHeaderProfile extends Component
{
    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    #[Renderless]
    public function attach(string $sftpConnection, CsvHeader $csvHeader): void
    {
        if ($sftpConnection === '') {
            $csvHeader->update(['sftp_connection_id' => null]);
            $this->success(__('SFTP removed from header profile.'));

            return;
        }

        $csvHeader->update(['sftp_connection_id' => (int) $sftpConnection]);
        $this->success(__('SFTP added to header profile.'));
    }

    public function render(): View
    {
        return view('livewire.creditor.sftp-connection.attach-header-profile')
            ->with([
                'sftpConnections' => app(SftpConnectionService::class)->fetchImportSftpConnections($this->user->company_id),
                'headers' => app(CsvHeaderService::class)->fetch($this->user->company_id, $this->user->subclient_id),
            ]);
    }
}
