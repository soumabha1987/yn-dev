<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class TooManyRequestsException extends Exception
{
    public function __construct(
        public string $component,
        public string $method,
        public ?string $ip,
        public int $secondsUntilAvailable,
    ) {
        parent::__construct(sprintf(
            'Too many requests from [%s] to method [%s] on component: [%s]. Retry in %d seconds.',
            $this->ip,
            $this->method,
            $this->component,
            $this->secondsUntilAvailable,
        ));
    }
}
