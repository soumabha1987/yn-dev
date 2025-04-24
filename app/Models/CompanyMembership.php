<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyMembershipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperCompanyMembership
 */
class CompanyMembership extends Pivot
{
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $casts = [
        'status' => CompanyMembershipStatus::class,
        'auto_renew' => 'boolean',
        'current_plan_start' => 'datetime',
        'current_plan_end' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function nextMembershipPlan(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'next_membership_plan_id');
    }
}
