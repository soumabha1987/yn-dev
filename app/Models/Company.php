<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BankAccountType;
use App\Enums\CompanyBusinessCategory;
use App\Enums\CompanyCategory;
use App\Enums\CompanyMembershipStatus;
use App\Enums\CompanyStatus;
use App\Enums\DebtType;
use App\Enums\IndustryType;
use App\Enums\Role;
use App\Enums\Timezone;
use App\Enums\YearlyVolumeRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @mixin IdeHelperCompany
 */
class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'status' => CompanyStatus::class,
        'is_super_admin_company' => 'boolean',
        'is_deactivate' => 'boolean',
        'dob' => 'date',
        'bank_account_type' => BankAccountType::class,
        'yearly_volume_range' => YearlyVolumeRange::class,
        'company_category' => CompanyCategory::class,
        'industry_type' => IndustryType::class,
        'business_category' => CompanyBusinessCategory::class,
        'debt_type' => DebtType::class,
        'timezone' => Timezone::class,
        'from_time' => 'datetime',
        'to_time' => 'datetime',
        'tilled_payment_response' => 'array',
        'approved_at' => 'datetime',
    ];

    public function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->company_name)->withoutObjectCaching();
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('notSuperAdmin', function (Builder $builder) {
            $builder->where('is_super_admin_company', false);
        });
    }

    public function subclients(): HasMany
    {
        return $this->hasMany(Subclient::class);
    }

    public function consumers(): HasMany
    {
        return $this->hasMany(Consumer::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function creditorUser(): HasOne
    {
        return $this->hasOne(User::class)
            ->ofMany([], function (Builder $query): void {
                $query->whereNull('parent_id')
                    ->whereRelation('roles', 'name', Role::CREDITOR)
                    ->whereNull('blocked_at')
                    ->whereNull('blocker_user_id');
            });
    }

    public function membershipPaymentProfile(): HasOne
    {
        return $this->hasOne(MembershipPaymentProfile::class);
    }

    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    public function merchant(): HasOne
    {
        return $this->hasOne(Merchant::class)
            ->ofMany([], function (Builder $query): void {
                $query->whereNull('subclient_id');
            });
    }

    public function personalizedLogo(): HasOne
    {
        return $this->hasOne(PersonalizedLogo::class);
    }

    public function transaction(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function companyMemberships(): HasMany
    {
        return $this->hasMany(CompanyMembership::class);
    }

    public function activeCompanyMembership(): HasOne
    {
        return $this->hasOne(CompanyMembership::class)
            ->ofMany([], function (Builder $query): void {
                $query->where('status', CompanyMembershipStatus::ACTIVE)
                    ->where('current_plan_end', '>=', Carbon::today());
            });
    }

    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(Membership::class)
            ->using(CompanyMembership::class)
            ->withPivot(['id', 'current_plan_start', 'current_plan_end', 'status', 'auto_renew'])
            ->withTimestamps();
    }

    public function specialMembership(): HasOne
    {
        return $this->hasOne(Membership::class);
    }

    public function automatedCommunicationHistories(): HasMany
    {
        return $this->hasMany(AutomatedCommunicationHistory::class);
    }

    public function customContents(): HasMany
    {
        return $this->hasMany(CustomContent::class);
    }

    public function scheduleExports(): HasMany
    {
        return $this->hasMany(ScheduleExport::class);
    }

    public function ynTransactions(): HasMany
    {
        return $this->hasMany(YnTransaction::class);
    }

    public function membershipTransactions(): HasMany
    {
        return $this->hasMany(MembershipTransaction::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function eLetters(): HasMany
    {
        return $this->hasMany(ELetter::class);
    }
}
