<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeatureName;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperFeatureFlag
 */
class FeatureFlag extends Model
{
    protected $casts = [
        'feature_name' => FeatureName::class,
        'status' => 'boolean',
    ];
}
