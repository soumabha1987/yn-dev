<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Consumer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class IsValidConsumerRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        /** @var Consumer $consumer */
        $consumer = $request->user();

        $routeConsumer = $request->route()->parameter('consumer');

        if ($routeConsumer) {
            if (! ($routeConsumer instanceof Consumer)) {
                $routeConsumer = Consumer::query()->findOrFail($routeConsumer);
            }

            if ($consumer->id === $routeConsumer->id) {
                return $next($request);
            }

            if (
                $consumer->dob->toDateString() === $routeConsumer->dob->toDateString()
                && $consumer->last_name === $routeConsumer->last_name
                && $consumer->last4ssn === $routeConsumer->last4ssn
            ) {
                return $next($request);
            }
        }

        abort(
            Response::HTTP_NOT_FOUND,
            __('It looks like you\'ve tried to access a resource or perform an action that you\'re not authorized to do.')
        );
    }
}
