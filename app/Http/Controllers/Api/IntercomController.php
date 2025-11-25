<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IntercomJwtService;
use App\Traits\RespondsWithHttpStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class IntercomController extends Controller
{
    use RespondsWithHttpStatus;

    private IntercomJwtService $jwtService;

    public function __construct(IntercomJwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Generate JWT token for authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateToken(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'sometimes|string|in:web,android,ios'
        ]);

        try {
            $user = Auth::user();
            
            if (!$user) {
                return $this->failure('User not authenticated', 401);
            }

            $platform = $request->input('platform', 'web');
            $authData = $this->jwtService->generateIntercomAuthData($user, $platform);

            return $this->success($authData, 'JWT token generated successfully');
        } catch (\Exception $e) {
            return $this->failure('Failed to generate JWT token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify JWT token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        try {
            $decoded = $this->jwtService->verifyToken($request->token);
            
            return $this->success([
                'user_id' => $decoded->user_id,
                'email' => $decoded->email,
                'name' => $decoded->name,
                'platform' => $decoded->platform ?? 'web',
                'expires_at' => $decoded->exp
            ], 'Token is valid');
        } catch (\Exception $e) {
            return $this->failure('Token verification failed: ' . $e->getMessage(), 401);
        }
    }

    /**
     * Get user information from JWT token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserFromToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        try {
            $user = $this->jwtService->getUserFromToken($request->token);
            
            if (!$user) {
                return $this->failure('User not found or token invalid', 404);
            }

            return $this->success([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ], 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->failure('Failed to retrieve user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate Intercom user hash for secure mode
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateUserHash(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'platform' => 'sometimes|string|in:web,android,ios'
            ]);
            
            $user = Auth::user();
            
            if (!$user) {
                return $this->failure('User not authenticated', 401);
            }

            $platform = $request->input('platform', 'web');
            $userHash = $this->jwtService->generateIntercomUserHash($user, $platform);

            return $this->success([
                'user_hash' => $userHash,
                'user_id' => $user->id,
                'platform' => $platform
            ], 'User hash generated successfully');
        } catch (\Exception $e) {
            return $this->failure('Failed to generate user hash: ' . $e->getMessage(), 500);
        }
    }
}