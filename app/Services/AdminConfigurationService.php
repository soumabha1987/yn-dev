<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminConfiguration;
use Illuminate\Database\Eloquent\Collection;

class AdminConfigurationService
{
    public function fetch(): Collection
    {
        return AdminConfiguration::query()->get();
    }
}
