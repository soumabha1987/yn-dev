<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AutomationCampaignFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperAutomationCampaign
 */
class AutomationCampaign extends Model
{
    use HasFactory;

    protected $casts = [
        'frequency' => AutomationCampaignFrequency::class,
        'enabled' => 'boolean',
        'last_sent_at' => 'datetime',
        'start_at' => 'datetime',
    ];

    public function communicationStatus(): BelongsTo
    {
        return $this->belongsTo(CommunicationStatus::class);
    }

    public function automatedCommunicationHistories(): HasMany
    {
        return $this->hasMany(AutomatedCommunicationHistory::class);
    }
}
