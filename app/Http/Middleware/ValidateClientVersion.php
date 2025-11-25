<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Composer\Semver\Comparator;

class ValidateClientVersion
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('production')) {
            $clientVersion = $request->header('X-Client-Version');

            $appVersion = env('CLIENT_APP_VERSION', '2.0.0');

            if (!$clientVersion || Comparator::lessThan($clientVersion, $appVersion)) {
                abort(426, 'Your app version is outdated. Please upgrade to the latest version.');
            }
        }

        return $next($request);
    }
}
