<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FeatureName;
use App\Models\FeatureFlag;
use Illuminate\Support\Collection;

class FeatureFlagService
{
    public function fetch(): Collection
    {
        return FeatureFlag::query()->get();
    }

    public function disabled(FeatureName $featureName): bool
    {
        return FeatureFlag::query()
            ->where('feature_name', $featureName)
            ->where('status', false)
            ->exists();
    }
}
