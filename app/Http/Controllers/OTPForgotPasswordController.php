<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OTPCode;
use App\Models\User;
use App\Helpers\ResponseHelper;
use App\Services\OTPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * OTPForgotPasswordController
 *
 * Handles the forgot password flow:
 * 1. Request OTP via email
 * 2. Verify OTP
 * 3. Reset password
 */
class OTPForgotPasswordController extends Controller
{
    protected $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Step 1: Request OTP for password reset.
     *
     * Validates email, generates and sends OTP.
     * Rate limited to 5 attempts per minute.
     */
    public function requestOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', 422, $validator->errors());
        }

        $email = $request->input('email');

        try {
            // Generate and send OTP
            $otpResult = $this->otpService->sendOTP($email);

            if (!$otpResult['success']) {
                return ResponseHelper::error($otpResult['message'], $otpResult['code']);
            }

            return ResponseHelper::success(
                'OTP sent successfully. Please check your email.',
                ['email' => $email]
            );

        } catch (\Exception $e) {
            \Log::error('OTP request error: ' . $e->getMessage());

            return ResponseHelper::error(
                'Failed to send OTP. Please try again.',
                500,
                app()->environment('local') ? ['error' => $e->getMessage()] : null
            );
        }
    }

    /**
     * Step 2: Verify OTP.
     *
     * If valid, returns the user's email so frontend can proceed to reset password.
     * If invalid, increments attempts and returns error after 5 failures.
     */
    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', 422, $validator->errors());
        }

        $email = $request->input('email');
        $otp = $request->input('otp');

        try {
            $result = $this->otpService->verifyOTP($email, $otp);

            if (!$result['success']) {
                return ResponseHelper::error($result['message'], $result['code'], $result['errors'] ?? null);
            }

            return ResponseHelper::success(
                'OTP verified successfully. You can now reset your password.',
                ['email' => $email]
            );

        } catch (\Exception $e) {
            \Log::error('OTP verification error: ' . $e->getMessage());

            return ResponseHelper::error(
                'OTP verification failed. Please try again.',
                500
            );
        }
    }

    /**
     * Step 3: Reset password after OTP verification.
     *
     * Requires valid OTP verification for the email.
     * Returns success after password update.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', 422, $validator->errors());
        }

        $email = $request->input('email');

        try {
            // Check if OTP was verified for this email
            $otpVerified = $this->otpService->isOTPVerified($email);

            if (!$otpVerified) {
                return ResponseHelper::error(
                    'OTP verification required before resetting password.',
                    403
                );
            }

            // Find and update user
            $user = User::where('email', $email)->firstOrFail();
            $user->password = Hash::make($request->input('password'));
            $user->save();

            // Invalidate all existing tokens for security
            $this->invalidateUserTokens($user);

            // Clear OTP session data
            $this->otpService->clearOTPData($email);

            return ResponseHelper::success(
                'Password reset successfully. Please log in with your new password.',
                ['email' => $email]
            );

        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());

            return ResponseHelper::error(
                'Password reset failed. Please try again.',
                500
            );
        }
    }

    /**
     * Resend OTP for users who didn't receive it or expired.
     */
    public function resendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', 422, $validator->errors());
        }

        $email = $request->input('email');

        try {
            $result = $this->otpService->resendOTP($email);

            if (!$result['success']) {
                return ResponseHelper::error($result['message'], $result['code']);
            }

            return ResponseHelper::success(
                'OTP resent successfully.',
                ['email' => $email, 'expires_in_minutes' => $result['data']['expires_in_minutes'] ?? 10]
            );

        } catch (\Exception $e) {
            \Log::error('OTP resend error: ' . $e->getMessage());

            return ResponseHelper::error(
                'Failed to resend OTP. Please try again.',
                500
            );
        }
    }

    /**
     * Invalidate all existing tokens for a user after password reset.
     */
    private function invalidateUserTokens(User $user): void
    {
        // Revoke all Sanctum tokens
        $user->tokens()->delete();

        // Revoke all tracked refresh tokens
        \App\Models\RefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}