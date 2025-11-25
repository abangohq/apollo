<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyHookDeck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validateHookdeck = in_array(env('APP_ENV'), ['production', 'staging']);

        if($validateHookdeck) {
            $headers = $request->headers->all();

            $signature = $request->header('X-Pikka-Hookdeck');

            $secret = env('APP_KEY');

            if ($signature !== $secret) {
                return response()->json(['error' => 'Invalid hook-deck signature!'], 403);
            }

            $filteredHeaders = array_filter($headers, function ($key) {
                return stripos($key, 'Hookdeck') === false;
            }, ARRAY_FILTER_USE_KEY);

            $request->headers->replace($filteredHeaders);
        }

        return $next($request);
    }
}
