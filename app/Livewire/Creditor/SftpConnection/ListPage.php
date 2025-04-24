<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\SftpConnection;

use App\Livewire\Traits\WithPagination;
use App\Models\SftpConnection;
use App\Models\User;
use App\Services\SftpConnectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;

class ListPage extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    #[Renderless]
    public function toggleEnabled(SftpConnection $sftpConnection): void
    {
        $sftpConnection->update(['enabled' => ! $sftpConnection->enabled]);

        $this->success(__('SFTP Connection :enabled successfully.', [
            'enabled' => $sftpConnection->enabled ? 'enabled' : 'disabled',
        ]));
    }

    public function delete(SftpConnection $sftpConnection): void
    {
        if ($sftpConnection->csvHeaders()->exists()) {
            $this->dispatch('close-confirmation-box');

            $this->error(__('Cannot deleted, this SFTP connection is linked to header files.'));

            return;
        }

        $sftpConnection->delete();

        $this->dispatch('close-confirmation-box');

        $this->success(__('SFTP deleted.'));
    }

    public function render(): View
    {
        $data = [
            'per_page' => $this->perPage,
            'company_id' => $this->user->company_id,
            'search' => $this->search,
        ];

        return view('livewire.creditor.sftp-connection.list-page')
            ->with('sftpConnections', app(SftpConnectionService::class)->fetch($data))
            ->title('SFTP Connection(s)');
    }
}
