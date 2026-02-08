<?php

namespace App\Services;

use App\Models\UserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OTPService
{
    /**
     * Maximum OTP attempts before locking
     */
    private const MAX_OTP_ATTEMPTS = 5;

    /**
     * OTP expiration time in minutes
     */
    private const OTP_EXPIRATION_MINUTES = 10;

    /**
     * Generate a 6-digit OTP
     */
    public function generateOTP(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Hash the OTP for secure storage
     */
    public function hashOTP(string $otp): string
    {
        return Hash::make($otp);
    }

    /**
     * Verify OTP against stored hash
     */
    public function verifyOTP(string $otp, string $storedHash): bool
    {
        return Hash::check($otp, $storedHash);
    }

    /**
     * Create a new OTP request for user registration
     */
    public function createOTPRequest(string $name, string $email, string $password, int $roleId = 2): UserRequest
    {
        $otp = $this->generateOTP();
        $otpHash = $this->hashOTP($otp);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRATION_MINUTES);

        return UserRequest::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'otp_hash' => $otpHash,
            'otp_expires_at' => $expiresAt,
            'otp_attempts' => 0,
            'role_id' => $roleId,
            'verification_status' => 'PENDING',
        ]);
    }

    /**
     * Verify OTP for a user request
     */
    public function verifyOTPRequest(string $email, string $otp): array
    {
        $userRequest = UserRequest::where('email', $email)
            ->where('verification_status', '!=', 'EXPIRED')
            ->first();

        if (!$userRequest) {
            return [
                'success' => false,
                'message' => 'User request not found',
                'code' => 404,
            ];
        }

        if ($userRequest->verification_status === 'VERIFIED') {
            return [
                'success' => false,
                'message' => 'OTP already verified',
                'code' => 400,
            ];
        }

        if ($userRequest->isOtpExpired()) {
            $userRequest->verification_status = 'EXPIRED';
            $userRequest->save();

            return [
                'success' => false,
                'message' => 'OTP has expired. Please request a new OTP.',
                'code' => 400,
            ];
        }

        if ($userRequest->isMaxOtpAttemptsReached()) {
            $userRequest->verification_status = 'EXPIRED';
            $userRequest->save();

            return [
                'success' => false,
                'message' => 'Maximum OTP attempts reached. Please request a new OTP.',
                'code' => 400,
            ];
        }

        if (!$this->verifyOTP($otp, $userRequest->otp_hash)) {
            $userRequest->incrementOtpAttempts();

            $attemptsLeft = self::MAX_OTP_ATTEMPTS - $userRequest->otp_attempts;

            if ($attemptsLeft <= 0) {
                $userRequest->verification_status = 'EXPIRED';
                $userRequest->save();
            }

            return [
                'success' => false,
                'message' => 'Invalid OTP',
                'code' => 400,
                'attempts_left' => max(0, $attemptsLeft),
            ];
        }

        // OTP is valid - mark as verified
        $userRequest->verification_status = 'VERIFIED';
        $userRequest->otp_attempts = 0;
        $userRequest->save();

        return [
            'success' => true,
            'message' => 'OTP verified successfully',
            'code' => 200,
            'data' => [
                'user_request_id' => $userRequest->id,
                'email' => $userRequest->email,
                'name' => $userRequest->name,
            ],
        ];
    }

    /**
     * Resend OTP for a pending request
     */
    public function resendOTP(string $email): array
    {
        $userRequest = UserRequest::where('email', $email)
            ->whereIn('verification_status', ['PENDING', 'EXPIRED'])
            ->first();

        if (!$userRequest) {
            return [
                'success' => false,
                'message' => 'User request not found or already verified',
                'code' => 404,
            ];
        }

        // Generate new OTP
        $otp = $this->generateOTP();
        $otpHash = $this->hashOTP($otp);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRATION_MINUTES);

        $userRequest->otp_hash = $otpHash;
        $userRequest->otp_expires_at = $expiresAt;
        $userRequest->otp_attempts = 0;
        $userRequest->verification_status = 'PENDING';
        $userRequest->save();

        return [
            'success' => true,
            'message' => 'OTP resent successfully',
            'code' => 200,
            'data' => [
                'expires_at' => $expiresAt,
            ],
        ];
    }

    /**
     * Get user request by email
     */
    public function getUserRequest(string $email): ?UserRequest
    {
        return UserRequest::where('email', $email)->first();
    }

    /**
     * Check verification status
     */
    public function getVerificationStatus(string $email): array
    {
        $userRequest = UserRequest::where('email', $email)->first();

        if (!$userRequest) {
            return [
                'success' => false,
                'message' => 'User request not found',
                'code' => 404,
            ];
        }

        return [
            'success' => true,
            'data' => [
                'email' => $userRequest->email,
                'name' => $userRequest->name,
                'verification_status' => $userRequest->verification_status,
                'is_verified' => $userRequest->verification_status === 'VERIFIED',
                'is_pending' => $userRequest->verification_status === 'PENDING',
                'is_expired' => $userRequest->verification_status === 'EXPIRED',
                'created_at' => $userRequest->created_at,
                'expires_at' => $userRequest->otp_expires_at,
            ],
        ];
    }

    /**
     * Delete expired OTP requests (cleanup)
     */
    public function cleanupExpiredRequests(): int
    {
        return UserRequest::where('verification_status', 'EXPIRED')
            ->orWhere('otp_expires_at', '<', now())
            ->delete();
    }
}
