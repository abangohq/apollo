<?php

namespace App\Http\Middleware;

use App\Services\IntercomJwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class IntercomJwtMiddleware
{
    private IntercomJwtService $jwtService;

    public function __construct(IntercomJwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('X-Intercom-JWT');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'JWT token is required',
                'data' => null
            ], 401);
        }

        try {
            $decoded = $this->jwtService->verifyToken($token);
            
            // Add decoded token data to request for use in controllers
            $request->merge([
                'jwt_user_id' => $decoded->user_id,
                'jwt_email' => $decoded->email,
                'jwt_name' => $decoded->name,
                'jwt_payload' => $decoded
            ]);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired JWT token: ' . $e->getMessage(),
                'data' => null
            ], 401);
        }
    }
}