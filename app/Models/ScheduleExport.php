<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewReportType;
use App\Enums\ScheduleExportFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperScheduleExport
 */
class ScheduleExport extends Model
{
    use HasFactory;

    protected $casts = [
        'report_type' => NewReportType::class,
        'frequency' => ScheduleExportFrequency::class,
        'pause' => 'boolean',
        'emails' => 'array',
        'last_sent_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subclient(): BelongsTo
    {
        return $this->belongsTo(Subclient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sftpConnection(): BelongsTo
    {
        return $this->belongsTo(SftpConnection::class);
    }

    public function csvHeader(): BelongsTo
    {
        return $this->belongsTo(CsvHeader::class);
    }
}
