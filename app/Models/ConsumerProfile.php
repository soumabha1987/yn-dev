<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperConsumerProfile
 */
class ConsumerProfile extends Model
{
    use HasFactory;

    protected $casts = [
        'text_permission' => 'boolean',
        'email_permission' => 'boolean',
        'landline_call_permission' => 'boolean',
        'usps_permission' => 'boolean',
        'verified_at' => 'datetime',
        'is_communication_updated' => 'boolean',
    ];

    public function consumers(): HasMany
    {
        return $this->hasMany(Consumer::class);
    }

    public function savedCards(): HasMany
    {
        return $this->hasMany(SavedCard::class, 'consumer_profile_id');
    }
}
