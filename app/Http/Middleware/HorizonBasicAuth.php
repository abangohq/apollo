<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HorizonBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = config('horizon.basic_auth.username');
        $password = config('horizon.basic_auth.password');

        if ($request->getUser() === $username && $request->getPassword() === $password) {
            return $next($request);
        }

        // Return a 401 response to trigger the browser's Basic Auth prompt
        return response('Unauthorized.', 401, ['WWW-Authenticate' => 'Basic']);
    }
}
