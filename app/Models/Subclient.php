<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BankAccountType;
use App\Enums\CompanyCategory;
use App\Enums\CustomContentType;
use App\Enums\IndustryType;
use App\Enums\SubclientStatus;
use App\Enums\YearlyVolumeRange;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperSubclient
 */
class Subclient extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'status' => SubclientStatus::class,
        'has_merchant' => 'boolean',
        'tilled_profile_completed_at' => 'datetime',
        'dob' => 'date',
        'bank_account_type' => BankAccountType::class,
        'yearly_volume_range' => YearlyVolumeRange::class,
        'company_category' => CompanyCategory::class,
        'industry_type' => IndustryType::class,
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function personalizedLogo(): HasOne
    {
        return $this->hasOne(PersonalizedLogo::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function customContents(): HasMany
    {
        return $this->hasMany(CustomContent::class);
    }

    public function termsAndCondition(): HasOne
    {
        return $this->hasOne(CustomContent::class, 'subclient_id')
            ->where('type', CustomContentType::TERMS_AND_CONDITIONS);
    }

    public function consumers(): HasMany
    {
        return $this->hasMany(Consumer::class);
    }
}
