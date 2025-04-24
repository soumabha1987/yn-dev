<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperCampaignTrackerConsumer
 */
class CampaignTrackerConsumer extends Pivot
{
    use HasFactory;

    public $timestamps = false;

    public function campaignTracker(): BelongsTo
    {
        return $this->belongsTo(CampaignTracker::class);
    }
}
