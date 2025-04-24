<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipTransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperMembershipTransaction
 */
class MembershipTransaction extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => MembershipTransactionStatus::class,
        'response' => 'array',
        'plan_end_date' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class)->withTrashed();
    }
}
