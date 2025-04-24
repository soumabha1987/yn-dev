<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminConfigurationSlug;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperAdminConfiguration
 */
class AdminConfiguration extends Model
{
    protected $casts = [
        'slug' => AdminConfigurationSlug::class,
    ];
}
