<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MerchantName;
use App\Enums\MerchantType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperMerchant
 */
class Merchant extends Model
{
    use HasFactory;

    protected $casts = [
        'merchant_name' => MerchantName::class,
        'merchant_type' => MerchantType::class,
        'verified_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subclient(): BelongsTo
    {
        return $this->belongsTo(Subclient::class);
    }

    public function paymentProfiles(): HasMany
    {
        return $this->hasMany(PaymentProfile::class);
    }
}
