<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * Authenticate user and return JWT + Refresh Token
     */
    public function login(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        if (!$user->is_active) {
            throw new \Exception('User account is inactive');
        }

        return $this->issueTokens($user);
    }

    /**
     * Issue JWT and Refresh Token for user
     */
    public function issueTokens(User $user): array
    {
        // Generate JWT token
        $token = JWTAuth::claims([
            'tenant_id' => $user->tenant_id,
            'role' => $user->role,
        ])->fromUser($user);

        // Generate Refresh Token
        $refreshToken = $this->createRefreshToken($user);

        return [
            'access_token' => $token,
            'refresh_token' => $refreshToken->token_hash,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60, // Convert to seconds
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'tenant_id' => $user->tenant_id,
            ],
        ];
    }

    /**
     * Create a refresh token for user
     */
    public function createRefreshToken(User $user): RefreshToken
    {
        // Revoke existing tokens
        RefreshToken::where('user_id', $user->id)->delete();

        // Create new refresh token (7 days validity)
        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $hash = \Str::random(64)),
            'expires_at' => Carbon::now()->addDays(config('jwt.refresh_ttl') / 1440),
            'created_at' => now(),
        ]);

        return $refreshToken;
    }

    /**
     * Refresh the JWT token using refresh token
     */
    public function refreshToken(string $refreshTokenHash): ?array
    {
        $refreshToken = RefreshToken::where('token_hash', $refreshTokenHash)
            ->where('expires_at', '>', now())
            ->first();

        if (!$refreshToken) {
            return null;
        }

        $user = $refreshToken->user;

        if (!$user->is_active) {
            throw new \Exception('User account is inactive');
        }

        // Delete old refresh token
        $refreshToken->delete();

        // Issue new tokens
        return $this->issueTokens($user);
    }

    /**
     * Validate JWT token
     */
    public function validateToken(string $token): bool
    {
        try {
            JWTAuth::setToken($token)->authenticate();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get authenticated user from token
     */
    public function getAuthenticatedUser(): ?User
    {
        try {
            return JWTAuth::authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get JWT claims
     */
    public function getTokenClaims(string $token): ?array
    {
        try {
            $decoded = JWTAuth::setToken($token)->getPayload();
            return $decoded->getAll();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Logout - Revoke refresh token
     */
    public function logout(User $user): bool
    {
        RefreshToken::where('user_id', $user->id)->delete();
        return true;
    }
}