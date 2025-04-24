<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FileUploadHistoryStatus;
use App\Enums\FileUploadHistoryType;
use App\Models\FileUploadHistory;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class FileUploadHistoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function fetchByCompany(array $data): LengthAwarePaginator
    {
        return FileUploadHistory::query()
            ->with('subclient:id,subclient_name')
            ->select('id', 'subclient_id', 'filename', 'failed_filename', 'type', 'processed_count', 'total_records', 'failed_count', 'is_sftp_import', 'status', 'created_at')
            ->where('company_id', $data['company_id'])
            ->where('is_hidden', false)
            ->when($data['type_filter'], function (Builder $query) use ($data): void {
                $fileUploadHistoryType = FileUploadHistoryType::tryFrom($data['type_filter']);

                if ($fileUploadHistoryType) {
                    $query->where('type', $fileUploadHistoryType);
                }
            })
            ->orderBy($data['column'], $data['direction'])
            ->paginate($data['per_page']);
    }

    /**
     * @param  array{company_id: int, per_page: int}  $data
     */
    public function fetchOnlyCFPBReportType(array $data): LengthAwarePaginator
    {
        return FileUploadHistory::query()
            ->select('id', 'filename', 'created_at')
            ->withCount('activeConsumers')
            ->where('company_id', $data['company_id'])
            ->where('type', FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB)
            ->where('processed_count', '>', 0)
            ->where('cfpb_hidden', false)
            ->where('status', FileUploadHistoryStatus::COMPLETE)
            ->having('active_consumers_count', '>', 0)
            ->latest()
            ->paginate($data['per_page']);

    }
}
