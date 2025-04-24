<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckIfSsnIsRequired
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (session('required_ssn_verification')) {
            return to_route('consumer.verify_ssn');
        }

        return $next($request);
    }
}
