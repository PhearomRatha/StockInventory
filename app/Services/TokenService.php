<?php

namespace App\Services;

use App\Models\TokenRevocation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TokenService
{
    /**
     * Maximum login attempts before lockout
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Lockout time in minutes
     */
    private const LOCKOUT_MINUTES = 15;

    /**
     * Attempt counter key prefix
     */
    private const ATTEMPT_KEY_PREFIX = 'login_attempts_';

    /**
     * Attempt counter storage (using cache)
     */
    private function getAttemptKey(string $email): string
    {
        return self::ATTEMPT_KEY_PREFIX . $email;
    }

    /**
     * Get remaining login attempts
     */
    public function getRemainingAttempts(string $email): int
    {
        $attempts = cache()->get($this->getAttemptKey($email), 0);
        return max(0, self::MAX_LOGIN_ATTEMPTS - $attempts);
    }

    /**
     * Increment login attempts
     */
    public function incrementLoginAttempts(string $email): void
    {
        $key = $this->getAttemptKey($email);
        $attempts = cache()->get($key, 0) + 1;
        cache()->put($key, $attempts, now()->addMinutes(self::LOCKOUT_MINUTES));
    }

    /**
     * Clear login attempts
     */
    public function clearLoginAttempts(string $email): void
    {
        cache()->forget($this->getAttemptKey($email));
    }

    /**
     * Check if account is locked
     */
    public function isAccountLocked(string $email): bool
    {
        return cache()->has($this->getAttemptKey($email)) && 
               cache()->get($this->getAttemptKey($email)) >= self::MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Authenticate user and generate token
     */
    public function authenticateUser(string $email, string $password): array
    {
        // Check if account is locked
        if ($this->isAccountLocked($email)) {
            $lockedUntil = cache()->get($this->getAttemptKey($email) . '_until');
            return [
                'success' => false,
                'message' => 'Account is temporarily locked due to too many failed attempts. Try again later.',
                'code' => 423,
                'locked_until' => $lockedUntil,
            ];
        }

        // Find user with role
        $user = User::with('role')->where('email', $email)->first();

        if (!$user) {
            $this->incrementLoginAttempts($email);
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'code' => 401,
                'attempts_left' => $this->getRemainingAttempts($email),
            ];
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            $this->incrementLoginAttempts($email);
            
            $remainingAttempts = $this->getRemainingAttempts($email);
            
            if ($remainingAttempts <= 0) {
                cache()->put($this->getAttemptKey($email) . '_until', now()->addMinutes(self::LOCKOUT_MINUTES), self::LOCKOUT_MINUTES);
            }

            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'code' => 401,
                'attempts_left' => $remainingAttempts,
            ];
        }

        // Check if user is active
        if ($user->status !== 'ACTIVE') {
            $statusMessages = [
                'PENDING' => 'Your account is pending verification. Please complete OTP verification.',
                'INACTIVE' => 'Your account has been deactivated. Please contact administrator.',
            ];

            return [
                'success' => false,
                'message' => $statusMessages[$user->status] ?? 'Account is not active.',
                'code' => 403,
                'data' => [
                    'user_status' => $user->status,
                    'requires_verification' => $user->status === 'PENDING',
                ],
            ];
        }

        // Clear login attempts on successful authentication
        $this->clearLoginAttempts($email);

        // Create Sanctum token with custom metadata for revocation
        $token = $user->createToken('auth-token', ['*'], now()->addHours(24));
        
        // Store token for additional tracking
        TokenRevocation::create([
            'token_id' => $token->accessToken->id,
            'user_id' => $user->id,
            'expires_at' => $token->accessToken->expires_at,
        ]);

        return [
            'success' => true,
            'message' => 'Login successful',
            'code' => 200,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->name ?? 'User',
                    'role_id' => $user->role_id,
                    'status' => $user->status,
                ],
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at,
            ],
        ];
    }

    /**
     * Revoke a specific token
     */
    public function revokeToken(int $userId, string $tokenId): bool
    {
        $revoked = TokenRevocation::where('token_id', $tokenId)
            ->where('user_id', $userId)
            ->update(['revoked_at' => now()]);

        // Also revoke in Sanctum
        $user = User::find($userId);
        if ($user) {
            $user->tokens()->where('id', $tokenId)->delete();
        }

        return $revoked > 0;
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens(int $userId): int
    {
        // Revoke in our tracking table
        $count = TokenRevocation::where('user_id', $userId)
            ->active()
            ->update(['revoked_at' => now()]);

        // Revoke all Sanctum tokens
        $user = User::find($userId);
        if ($user) {
            $user->tokens()->delete();
        }

        return $count;
    }

    /**
     * Revoke current token (for logout)
     */
    public function revokeCurrentToken($accessToken): bool
    {
        $tokenId = $accessToken->id;
        $userId = $accessToken->tokenable_id;

        // Mark as revoked in our tracking
        TokenRevocation::where('token_id', $tokenId)
            ->where('user_id', $userId)
            ->update(['revoked_at' => now()]);

        // Delete the token
        return $accessToken->delete();
    }

    /**
     * Check if token is valid and not revoked
     */
    public function isTokenValid(int $userId, string $tokenId): bool
    {
        $revocation = TokenRevocation::where('token_id', $tokenId)
            ->where('user_id', $userId)
            ->first();

        if (!$revocation) {
            return false;
        }

        return is_null($revocation->revoked_at) && 
               $revocation->expires_at->isFuture();
    }

    /**
     * Get active token count for a user
     */
    public function getActiveTokenCount(int $userId): int
    {
        return TokenRevocation::where('user_id', $userId)
            ->active()
            ->count();
    }

    /**
     * Cleanup expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        return TokenRevocation::where('expires_at', '<', now())->delete();
    }
}
