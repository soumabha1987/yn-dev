<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperCampaign
 */
class Campaign extends Model
{
    use HasFactory;

    protected $casts = [
        'frequency' => CampaignFrequency::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'is_run_immediately' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function campaignTrackers(): HasMany
    {
        return $this->hasMany(CampaignTracker::class);
    }

    public function campaignTracker(): HasOne
    {
        return $this->campaignTrackers()->one()->latestOfMany();
    }
}
