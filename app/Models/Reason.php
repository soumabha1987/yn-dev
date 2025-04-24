<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperReason
 */
class Reason extends Model
{
    use HasFactory;

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function consumer(): HasMany
    {
        return $this->hasMany(Consumer::class);
    }
}
