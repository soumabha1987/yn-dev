<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MerchantType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPaymentProfile
 */
class PaymentProfile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'method' => MerchantType::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(Consumer::class);
    }

    public function subclient(): BelongsTo
    {
        return $this->belongsTo(Subclient::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function stripePaymentDetail(): HasOne
    {
        return $this->hasOne(StripePaymentDetail::class);
    }
}
