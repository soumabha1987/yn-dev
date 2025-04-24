<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum CustomContentType: string
{
    use Names;
    use Values;

    case ABOUT_US = 'about-us';
    case CONTACT_US = 'contact-us';
    case TERMS_AND_CONDITIONS = 'terms-conditions';
}
