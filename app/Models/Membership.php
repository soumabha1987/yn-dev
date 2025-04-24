<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipFrequency;
use App\Models\Traits\Sortable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperMembership
 */
class Membership extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Sortable;

    protected $casts = [
        'frequency' => MembershipFrequency::class,
        'status' => 'boolean',
        'meta_data' => 'array',
    ];

    public function features(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->meta_data['features'] ?? [],
            set: fn (array $value) => ['meta_data' => Json::encode([...($this->meta_data ?? []), 'features' => $value])],
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyMemberships(): HasMany
    {
        return $this->hasMany(CompanyMembership::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->using(CompanyMembership::class)
            ->withPivot(['id', 'current_plan_start', 'current_plan_end', 'status', 'auto_renew'])
            ->withTimestamps();
    }
}
