<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Carbon\Carbon;
use Exception;

class IntercomJwtService
{
    private string $secret;
    private string $algorithm;
    private int $expiration;

    public function __construct()
    {
        $this->secret = config('app.key');
        $this->algorithm = 'HS256';
        $this->expiration = 3600; // 1 hour
    }

    /**
     * Generate JWT token for Intercom authentication
     *
     * @param User $user
     * @param string $platform Platform type (web, android, ios)
     * @return string
     */
    public function generateToken(User $user, string $platform = 'web'): string
    {
        $payload = [
            'iss' => config('app.name'), // Issuer
            'sub' => $user->id, // Subject (user ID)
            'iat' => Carbon::now()->timestamp, // Issued at
            'exp' => Carbon::now()->addSeconds($this->expiration)->timestamp, // Expiration
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'platform' => $platform, // Platform identifier
            'created_at' => $user->created_at->timestamp,
        ];

        // Use platform-specific secret keys
        $secretKey = match($platform) {
            'web' => config('services.intercom.web_secret_key'),
            'ios' => config('services.intercom.ios_secret_key'),
            'android' => config('services.intercom.android_secret_key'),
            default => config('services.intercom.web_secret_key')
        };

        return JWT::encode($payload, $secretKey, $this->algorithm);
    }

    /**
     * Verify JWT token
     * @param string $token
     * @return object
     * @throws Exception
     */
    public function verifyToken(string $token): object
    {
        try {
            // Try to decode with all possible secret keys since we don't know the platform yet
            $secrets = [
                config('services.intercom.web_secret_key'),
                config('services.intercom.ios_secret_key'),
                config('services.intercom.android_secret_key')
            ];
            
            foreach ($secrets as $secret) {
                try {
                    return JWT::decode($token, new Key($secret, $this->algorithm));
                } catch (Exception $e) {
                    continue;
                }
            }
            
            throw new Exception('Invalid or expired token');
        } catch (Exception $e) {
            throw new Exception('Invalid or expired token: ' . $e->getMessage());
        }
    }

    /**
     * Decode JWT token without throwing exceptions
     * @param string $token
     * @return object|null
     */
    public function decodeToken(string $token): ?object
    {
        try {
            // Try to decode with all possible secret keys since we don't know the platform yet
            $secrets = [
                config('services.intercom.web_secret_key'),
                config('services.intercom.ios_secret_key'),
                config('services.intercom.android_secret_key')
            ];
            
            foreach ($secrets as $secret) {
                try {
                    return JWT::decode($token, new Key($secret, $this->algorithm));
                } catch (Exception $e) {
                    continue;
                }
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get user from JWT token
     *
     * @param string $token
     * @return User|null
     */
    public function getUserFromToken(string $token): ?User
    {
        try {
            $decoded = $this->verifyToken($token);
            return User::find($decoded->user_id);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Generate Intercom user hash for secure mode
     *
     * @param User $user
     * @param string $platform Platform type (web, android, ios)
     * @return string
     */
    public function generateIntercomUserHash(User $user, string $platform = 'web'): string
    {
        // Use platform-specific secret keys
        $secretKey = match($platform) {
            'web' => config('services.intercom.web_secret_key'),
            'ios' => config('services.intercom.ios_secret_key'),
            'android' => config('services.intercom.android_secret_key'),
            default => config('services.intercom.web_secret_key')
        };
        
        return hash_hmac('sha256', $user->id, $secretKey);
    }

    /**
     * Generate complete Intercom authentication data
     *
     * @param User $user
     * @param string $platform Platform type (web, android, ios)
     * @return array
     */
    public function generateIntercomAuthData(User $user, string $platform = 'web'): array
    {
        return [
            'jwt_token' => $this->generateToken($user, $platform),
            'user_hash' => $this->generateIntercomUserHash($user, $platform),
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'platform' => $platform,
            'created_at' => $user->created_at->timestamp,
        ];
    }
}