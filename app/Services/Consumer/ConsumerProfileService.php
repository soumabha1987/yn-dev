<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Models\ConsumerProfile;

class ConsumerProfileService
{
    public function getByEmail(string $email): ?ConsumerProfile
    {
        return ConsumerProfile::query()
            ->with('consumers')
            ->where('email', $email)
            ->first();
    }
}
