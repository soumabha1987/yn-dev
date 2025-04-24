<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewReportType;
use App\Enums\ReportHistoryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperReportHistory
 */
class ReportHistory extends Model
{
    use HasFactory;
    use Prunable;

    protected $casts = [
        'report_type' => NewReportType::class,
        'status' => ReportHistoryStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonths(3));
    }

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        self::deleting(function (self $reportHistory) {
            if ($reportHistory->status === ReportHistoryStatus::SUCCESS) {
                Storage::delete('download-report/' . Str::slug($reportHistory->report_type->value) . '/' . $reportHistory->downloaded_file_name);
            }
        });
    }

    public function subclient(): BelongsTo
    {
        return $this->belongsTo(Subclient::class);
    }
}
