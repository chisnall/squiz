<?php

namespace Chisnall\Squiz\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SquizToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get token from URL query parameter, otherwise from header
        // URL query parameter token is used for the view when visiting the page
        // Header token is used by JS requests
        $appToken = request()->get('token') ?? request()->header('X-SQUIZ-TOKEN');

        // Return now if local environment
        if (App::environment() == 'local') {
            return $next($request);
        }

        // Check if token is valid
        $isValid = $appToken === config('squiz.token');

        // Abort if token is invalid
        if (! $isValid) {
            abort(404);
        }

        return $next($request);
    }
}
