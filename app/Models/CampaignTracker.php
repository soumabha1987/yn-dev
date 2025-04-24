<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperCampaignTracker
 */
class CampaignTracker extends Model
{
    use HasFactory;

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function consumers(): BelongsToMany
    {
        return $this->belongsToMany(Consumer::class)
            ->withPivot(['click', 'cost']);
    }

    public function campaignTrackerConsumers(): HasMany
    {
        return $this->hasMany(CampaignTrackerConsumer::class);
    }
}
