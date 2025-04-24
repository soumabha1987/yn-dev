<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperConsumerNegotiation
 */
class ConsumerNegotiation extends Model
{
    use HasFactory;

    protected $casts = [
        'negotiation_type' => NegotiationType::class,
        'installment_type' => InstallmentType::class,
        'first_pay_date' => 'datetime',
        'offer_accepted' => 'boolean',
        'offer_accepted_at' => 'datetime',
        'counter_first_pay_date' => 'datetime',
        'counter_offer_accepted' => 'boolean',
        'active_negotiation' => 'boolean',
        'payment_plan_current_balance' => 'float',
        'one_time_settlement' => 'float',
        'negotiate_amount' => 'float',
        'monthly_amount' => 'float',
        'no_of_installments' => 'integer',
        'last_month_amount' => 'float',
        'counter_one_time_amount' => 'float',
        'counter_negotiate_amount' => 'float',
        'counter_monthly_amount' => 'float',
        'counter_no_of_installments' => 'integer',
    ];

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(Consumer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scheduleTransactions(): HasMany
    {
        return $this->hasMany(ScheduleTransaction::class, 'consumer_id', 'consumer_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'consumer_id', 'consumer_id');
    }
}
