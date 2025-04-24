<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperCommunicationStatus
 */
class CommunicationStatus extends Model
{
    use HasFactory;

    protected $casts = [
        'code' => CommunicationCode::class,
        'trigger_type' => CommunicationStatusTriggerType::class,
    ];

    public function automationCampaign(): HasOne
    {
        return $this->hasOne(AutomationCampaign::class);
    }

    public function automationCampaigns(): HasMany
    {
        return $this->hasMany(AutomationCampaign::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(AutomatedTemplate::class, 'automated_email_template_id');
    }

    public function smsTemplate(): BelongsTo
    {
        return $this->belongsTo(AutomatedTemplate::class, 'automated_sms_template_id');
    }
}
