<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AutomatedTemplateType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperAutomatedTemplate
 */
class AutomatedTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'type' => AutomatedTemplateType::class,
        'enabled' => 'boolean',
    ];

    public function content(): Attribute
    {
        return Attribute::make(
            get: fn (?string $content): ?string => $content ? html_entity_decode($content) : null,
            set: fn (?string $content): ?string => $content ? htmlentities($content) : null
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function automationCampaign(): HasMany
    {
        return $this->hasMany(AutomationCampaign::class);
    }

    public function emailTemplate(): HasOne
    {
        return $this->hasOne(CommunicationStatus::class, 'automated_email_template_id');
    }

    public function smsTemplate(): HasOne
    {
        return $this->hasOne(CommunicationStatus::class, 'automated_sms_template_id');
    }
}
