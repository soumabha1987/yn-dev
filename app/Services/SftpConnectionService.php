<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SftpConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SftpConnectionService
{
    /**
     * @param array{
     *    search: ?string,
     *    company_id: int,
     *    per_page: int,
     * } $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return SftpConnection::query()
            ->select('id', 'company_id', 'name', 'host', 'port', 'enabled', 'username', 'export_filepath', 'import_filepath')
            ->where('company_id', $data['company_id'])
            ->when($data['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->search('name', $search)
                        ->orSearch('username', $search);
                });
            })
            ->latest()
            ->paginate($data['per_page']);
    }

    public function fetchImportSftpConnections(int $companyId): array
    {
        return SftpConnection::query()
            ->where('enabled', true)
            ->whereNotNull('import_filepath')
            ->where('company_id', $companyId)
            ->pluck('name', 'id')
            ->all();
    }

    public function fetchExportSftpConnections(int $companyId): array
    {
        return SftpConnection::query()
            ->where('enabled', true)
            ->whereNotNull('export_filepath')
            ->where('company_id', $companyId)
            ->pluck('name', 'id')
            ->all();
    }

    public function enabledExists(int $companyId): bool
    {
        return SftpConnection::query()
            ->where('company_id', $companyId)
            ->where('enabled', true)
            ->exists();
    }
}
