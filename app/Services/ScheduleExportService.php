<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ScheduleExportDeliveryType;
use App\Enums\ScheduleExportFrequency;
use App\Models\Company;
use App\Models\ScheduleExport;
use App\Models\Subclient;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ScheduleExportService
{
    /**
     * @param  array{
     *  per_page: int,
     *  column: string,
     *  direction: string,
     *  user_id: int,
     *  company_id: ?int,
     *  is_super_admin: bool
     * }  $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return ScheduleExport::query()
            ->with(['company:id,company_name', 'sftpConnection:id,name', 'subclient:id,subclient_name'])
            ->when(
                $data['is_super_admin'],
                function (Builder $query): void {
                    $query->doesntHave('user.company');
                },
                function (Builder $query) use ($data): void {
                    $query->whereHas('user', fn (Builder $query) => $query->where('company_id', $data['company_id']));
                }
            )
            ->when($data['column'] === 'company_name', function (EloquentBuilder $query) use ($data): void {
                $query->when(
                    $data['is_super_admin'],
                    function (EloquentBuilder $query) use ($data): void {
                        $query->orderBy(Company::select($data['column'])
                            ->whereColumn('companies.id', 'schedule_exports.company_id'), $data['direction'])
                            ->orderBy('id');
                    },
                    function (EloquentBuilder $query) use ($data): void {
                        $query->orderBy(Subclient::select('subclient_name')
                            ->whereColumn('subclients.id', 'schedule_exports.subclient_id'), $data['direction'])
                            ->orderBy('id');
                    }
                );
            })
            ->when(
                in_array($data['column'], ['sftp_connection_id', 'frequency', 'report_type', 'created_at']),
                function (EloquentBuilder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            )
            ->paginate($data['per_page']);
    }

    public function fetchByFrequency(ScheduleExportFrequency $frequency): Collection
    {
        return ScheduleExport::query()
            ->with(['user:id,email', 'company', 'sftpConnection'])
            ->where('pause', false)
            ->where('frequency', $frequency)
            ->get();
    }

    /**
     * @param  array{
     *      is_super_admin: bool,
     *      report_type: string,
     *      user_id: int,
     *      company_id : int|string,
     *      subclient_id: int|string,
     *      frequency: string,
     *      csv_header_id: int|string,
     *      schedule_export_id: ?int,
     *      delivery_type: string,
     *      sftp_connection_id: ?int
     * }  $data
     */
    public function sameReportExists(array $data): ?ScheduleExport
    {
        return ScheduleExport::query()
            ->where('report_type', $data['report_type'])
            ->where('frequency', $data['frequency'])
            ->where('csv_header_id', $data['csv_header_id'])
            ->when(
                $data['is_super_admin'],
                function (Builder $query) use ($data): void {
                    $query->doesntHave('user.company')
                        ->where('company_id', $data['company_id']);
                },
                function (Builder $query) use ($data): void {
                    $query->whereHas('user', fn (Builder $query) => $query
                        ->where('company_id', $data['company_id']));
                }
            )
            ->when(
                $data['subclient_id'],
                function (Builder $query) use ($data): void {
                    $query->where('subclient_id', $data['subclient_id']);
                },
                function (Builder $query): void {
                    $query->whereNull('subclient_id');
                },
            )
            ->when($data['schedule_export_id'], function (Builder $query) use ($data): void {
                $query->whereNot('id', $data['schedule_export_id']);
            })
            ->when(
                $data['sftp_connection_id'] && $data['delivery_type'] === ScheduleExportDeliveryType::SFTP->value,
                function (Builder $query) use ($data): void {
                    $query->where('sftp_connection_id', $data['sftp_connection_id']);
                },
                function (Builder $query): void {
                    $query->whereNotNull('emails');
                },
            )
            ->first();
    }
}
