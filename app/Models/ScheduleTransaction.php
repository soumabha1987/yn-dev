<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperScheduleTransaction
 */
class ScheduleTransaction extends Model
{
    use HasFactory;

    protected $casts = [
        'transaction_type' => TransactionType::class,
        'schedule_date' => 'datetime',
        'schedule_time' => 'datetime',
        'previous_schedule_date' => 'datetime',
        'status' => TransactionStatus::class,
        'last_attempted_at' => 'datetime',
    ];

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(Consumer::class);
    }

    public function paymentProfile(): BelongsTo
    {
        return $this->belongsTo(PaymentProfile::class);
    }

    public function externalPaymentProfile(): BelongsTo
    {
        return $this->belongsTo(ExternalPaymentProfile::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subclient(): BelongsTo
    {
        return $this->belongsTo(Subclient::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function stripePaymentDetail(): BelongsTo
    {
        return $this->belongsTo(StripePaymentDetail::class, 'stripe_payment_detail_id');
    }
}
