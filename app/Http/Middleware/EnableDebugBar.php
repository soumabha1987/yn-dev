<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Barryvdh\Debugbar\Facades\Debugbar;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnableDebugBar
{
    protected const COOKIE_NAME = 'debugbar_enabled_UDCERODHWI';

    protected const QUERY_PARAM = 'debug_TOJUJPZGCP';

    protected const COOKIE_DURATION_MINUTES = 60 * 24; // 24 hours

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query(self::QUERY_PARAM) === 'true' || $request->cookie(self::COOKIE_NAME) === 'true') {
            Debugbar::enable();

            $response = $next($request);

            if ($request->query(self::QUERY_PARAM) === 'true' && ! $request->cookie(self::COOKIE_NAME)) {
                $response->cookie(self::COOKIE_NAME, 'true', self::COOKIE_DURATION_MINUTES);
            }

            return $response;
        }

        if ($request->query(self::QUERY_PARAM) === 'false') {
            Debugbar::disable();

            $response = $next($request);
            $response->cookie(self::COOKIE_NAME, 'false', self::COOKIE_DURATION_MINUTES);

            return $response;
        }

        return $next($request);
    }
}
