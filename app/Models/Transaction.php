<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperTransaction
 */
class Transaction extends Model
{
    use HasFactory;

    protected $casts = [
        'transaction_type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'superadmin_process' => 'boolean',
        'gateway_response' => 'array',
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

    public function scheduleTransaction(): HasOne
    {
        return $this->scheduleTransactions()->one()->latestOfMany();
    }

    public function scheduleTransactions(): HasMany
    {
        return $this->hasMany(ScheduleTransaction::class);
    }

    public function paymentProfile(): BelongsTo
    {
        return $this->belongsTo(PaymentProfile::class);
    }

    public function externalPaymentProfile(): BelongsTo
    {
        return $this->belongsTo(ExternalPaymentProfile::class);
    }

    public function paymentProfileWithTrash(): BelongsTo
    {
        return $this->belongsTo(PaymentProfile::class, 'payment_profile_id', 'id')->withTrashed();
    }
}
