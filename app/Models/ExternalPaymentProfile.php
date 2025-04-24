<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MerchantType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperExternalPaymentProfile
 */
class ExternalPaymentProfile extends Model
{
    use HasFactory;

    protected $casts = [
        'method' => MerchantType::class,
    ];

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

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function scheduleTransaction(): HasOne
    {
        return $this->hasOne(ScheduleTransaction::class);
    }
}
