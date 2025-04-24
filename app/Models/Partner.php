<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperPartner
 */
class Partner extends Model
{
    use HasFactory;

    protected $casts = [
        'report_emails' => 'array',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
