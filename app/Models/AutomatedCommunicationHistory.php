<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AutomatedCommunicationHistoryStatus;
use App\Enums\AutomatedTemplateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperAutomatedCommunicationHistory
 */
class AutomatedCommunicationHistory extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => AutomatedCommunicationHistoryStatus::class,
        'automated_template_type' => AutomatedTemplateType::class,
    ];

    public function communicationStatus(): BelongsTo
    {
        return $this->belongsTo(CommunicationStatus::class);
    }

    public function automationCampaign(): BelongsTo
    {
        return $this->belongsTo(AutomationCampaign::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subclient(): BelongsTo
    {
        return $this->belongsTo(Subclient::class);
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(Consumer::class);
    }

    public function automatedTemplate(): BelongsTo
    {
        return $this->belongsTo(AutomatedTemplate::class);
    }
}
