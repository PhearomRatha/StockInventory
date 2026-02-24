<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EmailVerificationService
{
    /**
     * Verification token expiration time in minutes
     */
    private const TOKEN_EXPIRATION_MINUTES = 60; // 1 hour

    /**
     * Generate a secure verification token
     */
    public function generateToken(): string
    {
        return hash('sha256', Str::random(64));
    }

    /**
     * Get token expiration time
     */
    public function getExpirationTime(): \Carbon\Carbon
    {
        return now()->addMinutes(self::TOKEN_EXPIRATION_MINUTES);
    }

    /**
     * Create verification token for user
     */
    public function createVerificationToken(User $user): string
    {
        $token = $this->generateToken();

        $user->update([
            'verification_token' => hash('sha256', $token),
            'verification_expires_at' => $this->getExpirationTime(),
        ]);

        return $token;
    }

    /**
     * Verify token and mark user as verified
     */
    public function verifyToken(string $token): array
    {
        $hashedToken = hash('sha256', $token);

        $user = User::where('verification_token', $hashedToken)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid verification token.',
                'code' => 400,
            ];
        }

        if ($user->email_verified_at !== null) {
            return [
                'success' => false,
                'message' => 'Email already verified.',
                'code' => 400,
            ];
        }

        if (!$user->verification_expires_at || now()->isAfter($user->verification_expires_at)) {
            return [
                'success' => false,
                'message' => 'Verification token has expired. Please request a new one.',
                'code' => 400,
            ];
        }

        // Mark user as verified
        $user->update([
            'email_verified_at' => now(),
            'verification_token' => null,
            'verification_expires_at' => null,
            'status' => 'ACTIVE',
        ]);

        return [
            'success' => true,
            'message' => 'Email verified successfully!',
            'code' => 200,
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
            ],
        ];
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(User $user): array
    {
        if ($user->email_verified_at !== null) {
            return [
                'success' => false,
                'message' => 'Email already verified.',
                'code' => 400,
            ];
        }

        // Create new token
        $token = $this->createVerificationToken($user);

        return [
            'success' => true,
            'message' => 'Verification email sent.',
            'code' => 200,
            'data' => [
                'expires_in_minutes' => self::TOKEN_EXPIRATION_MINUTES,
                'verification_token' => env('APP_DEBUG', false) ? $token : null,
            ],
        ];
    }

    /**
     * Check if token is valid
     */
    public function isTokenValid(User $user, string $token): bool
    {
        if ($user->email_verified_at !== null) {
            return false;
        }

        if (!$user->verification_token || !$user->verification_expires_at) {
            return false;
        }

        if (now()->isAfter($user->verification_expires_at)) {
            return false;
        }

        return hash('sha256', $token) === $user->verification_token;
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        return User::whereNotNull('verification_token')
            ->where('verification_expires_at', '<', now())
            ->update([
                'verification_token' => null,
                'verification_expires_at' => null,
            ]);
    }
}
