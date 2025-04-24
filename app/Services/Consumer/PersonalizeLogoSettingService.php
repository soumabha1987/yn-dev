<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Models\Consumer;
use App\Models\ConsumerPersonalizedLogo;

class PersonalizeLogoSettingService
{
    /**
     * @param  array<string, string>  $data
     */
    public function createOrUpdateConsumerPersonalizeLogo(array $data, Consumer $consumer): ConsumerPersonalizedLogo
    {
        return ConsumerPersonalizedLogo::query()
            ->updateOrCreate(
                [
                    'consumer_id' => $consumer->id,
                ],
                [
                    'primary_color' => $data['primaryColor'],
                    'secondary_color' => $data['secondaryColor'],
                ],
            );
    }

    public function removeConsumerPersonalizeLogo(Consumer $consumer): void
    {
        ConsumerPersonalizedLogo::query()
            ->where('consumer_id', $consumer->id)
            ->delete();
    }
}
