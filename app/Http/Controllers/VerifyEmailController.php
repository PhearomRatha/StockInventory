<?php

namespace App\Http\Controllers;

use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VerifyEmailController extends Controller
{
    protected $emailVerificationService;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
    }

    /**
     * Verify email with token
     */
    public function verify($token)
    {
        try {
            $result = $this->emailVerificationService->verifyToken($token);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], $result['code']);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
            ], $result['code']);

        } catch (\Exception $e) {
            Log::error('Email verification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Email verification failed',
            ], 500);
        }
    }

    /**
     * Resend verification email
     */
    public function resend(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = \App\Models\User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found with this email address.',
                ], 404);
            }

            $result = $this->emailVerificationService->resendVerificationEmail($user);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
            ], $result['code']);

        } catch (\Exception $e) {
            Log::error('Resend verification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend verification email',
            ], 500);
        }
    }

    /**
     * Check verification status
     */
    public function checkStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = \App\Models\User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found with this email address.',
                ], 404);
            }

            $isVerified = $user->email_verified_at !== null;

            return response()->json([
                'success' => true,
                'is_verified' => $isVerified,
                'message' => $isVerified ? 'Email is verified.' : 'Email is not verified.',
                'data' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Check verification status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check verification status',
            ], 500);
        }
    }
}
