<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Models\PersonalizedLogo;

class PersonalizedLogoService
{
    public function fetchBySubdomain(string $subdomain): ?PersonalizedLogo
    {
        return PersonalizedLogo::query()
            ->where('customer_communication_link', $subdomain)
            ->first();
    }
}
