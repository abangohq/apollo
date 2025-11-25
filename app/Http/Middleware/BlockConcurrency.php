<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BlockConcurrency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, mixed $key): Response
    {
        $hashKey = "route-block-{$key}-{$request->user()->id}";

        $this->hashCompare($request, $hashKey);

        $response = $next($request);
 
        Cache::forget($hashKey);
 
        return $response;
    }

    /**
     * Handle the hash comparism and abort if need be.
     */
    public function hashCompare(Request $request, string $hashKey)
    {
        $payload = json_encode($request->all());
        $hashedPayload = hash_hmac('sha512', $payload, $request->user()->id);
        $prevHashedPayload = Cache::get($hashKey);

        if (!is_null($prevHashedPayload)) {
            if ($prevHashedPayload === $hashedPayload) {
                abort(429, 'Withdrawal request already in progress please wait for some minutes before retrying.');
            }
        } else {
            Cache::put($hashKey, $hashedPayload, 60);
        }
    }
    
    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
       //
    }
}
