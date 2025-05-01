<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsumerStatus;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as AuthMustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

/**
 * @mixin IdeHelperConsumer
 */
class Consumer extends Model implements AuthenticatableContract, AuthMustVerifyEmail
{
    use Authenticatable;
    use HasFactory;
    use MustVerifyEmail;
    use Notifiable;
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'dob' => 'date',
        'status' => ConsumerStatus::class,
        'payment_setup' => 'boolean',
        'counter_offer' => 'boolean',
        'has_failed_payment' => 'boolean',
        'custom_offer' => 'boolean',
        'offer_accepted' => 'boolean',
        'disputed_at' => 'datetime',
        'placement_date' => 'date',
        'expiry_date' => 'date',
        'restart_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->isDirty('status') && $model->status === ConsumerStatus::DEACTIVATED) {
                $model->disputed_at = now();
            }
        });
    }

    public function fullName(): Attribute
    {
        return Attribute::get(fn (): string => $this->first_name . ($this->middle_name ? ' ' . $this->middle_name : '') . ' ' . $this->last_name);
    }

    public function pluckUsernameFirstTwoDigits(): Attribute
    {
        return Attribute::get(fn (): string => mb_substr($this->first_name ?? '', 0, 1) . mb_substr($this->last_name ?? '', 0, 1));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function consumerNegotiation(): HasOne
    {
        return $this->hasOne(ConsumerNegotiation::class);
    }

    public function subclient(): BelongsTo
    {
        return $this->belongsTo(Subclient::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentProfile(): HasOne
    {
        return $this->hasOne(PaymentProfile::class)->latestOfMany()->withTrashed();
    }

    public function externalPaymentProfile(): HasOne
    {
        return $this->hasOne(ExternalPaymentProfile::class)->latestOfMany();
    }

    public function paymentProfiles(): HasMany
    {
        return $this->hasMany(PaymentProfile::class);
    }

    public function consumerLogs(): HasMany
    {
        return $this->hasMany(ConsumerLog::class);
    }

    public function unsubscribe(): HasOne
    {
        return $this->hasOne(ConsumerUnsubscribe::class);
    }

    public function consumerProfile(): BelongsTo
    {
        return $this->belongsTo(ConsumerProfile::class);
    }

    public function scheduledTransactions(): HasMany
    {
        return $this->hasMany(ScheduleTransaction::class);
    }

    public function consumerPersonalizedLogo(): HasOne
    {
        return $this->hasOne(ConsumerPersonalizedLogo::class);
    }

    public function eLetters(): BelongsToMany
    {
        return $this->belongsToMany(ELetter::class)
            ->using(ConsumerELetter::class)
            ->withPivot(['enabled', 'read_by_consumer'])
            ->withTimestamps();
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(Reason::class);
    }

    public function campaignTrackerConsumer(): HasOne
    {
        return $this->hasOne(CampaignTrackerConsumer::class);
    }

    public function savedCards(): HasMany
    {
        return $this->hasMany(SavedCard::class, 'consumer_id');
    }
}
