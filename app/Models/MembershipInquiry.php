<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipInquiryStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperMembershipInquiry
 */
class MembershipInquiry extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => MembershipInquiryStatus::class,
        'accounts_in_scope' => 'integer',
    ];

    public function description(): Attribute
    {
        return Attribute::make(
            get: fn (?string $description): ?string => $description ? html_entity_decode($description) : null,
            set: fn (?string $description): ?string => $description ? htmlentities($description) : null
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
