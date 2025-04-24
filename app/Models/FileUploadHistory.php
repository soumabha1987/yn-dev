<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsumerStatus;
use App\Enums\FileUploadHistoryStatus;
use App\Enums\FileUploadHistoryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperFileUploadHistory
 */
class FileUploadHistory extends Model
{
    use HasFactory;

    protected $casts = [
        'type' => FileUploadHistoryType::class,
        'status' => FileUploadHistoryStatus::class,
        'cfpb_hidden' => 'boolean',
        'is_sftp_import' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subclient(): BelongsTo
    {
        return $this->belongsTo(Subclient::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function consumers(): HasMany
    {
        return $this->hasMany(Consumer::class);
    }

    public function activeConsumers(): HasMany
    {
        return $this->consumers()
            ->whereNotIn('status', [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]);
    }
}
