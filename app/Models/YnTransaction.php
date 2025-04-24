<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipTransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperYnTransaction
 */
class YnTransaction extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => MembershipTransactionStatus::class,
        'response' => 'array',
        'billing_cycle_start' => 'datetime',
        'billing_cycle_end' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scheduleTransaction(): BelongsTo
    {
        return $this->belongsTo(ScheduleTransaction::class);
    }
}
