<?php

namespace App\Http\Controllers;

use App\Mail\OTPMail;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\User;
use App\Models\UserRequest;
use App\Services\OTPService;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $otpService;
    protected $tokenService;

    public function __construct(OTPService $otpService, TokenService $tokenService)
    {
        $this->otpService = $otpService;
        $this->tokenService = $tokenService;
    }

    // Register user and send OTP
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:6|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            if (User::where('email', $data['email'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already registered. Please login.',
                ], 409);
            }

            if (UserRequest::where('email', $data['email'])
                ->whereIn('verification_status', ['PENDING', 'VERIFIED'])
                ->exists()
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration request already exists. Check your email or contact support.',
                ], 409);
            }

            $otp = $this->otpService->generateOTP();
            $expiresAt = now()->addMinutes(10);

            $userRequest = UserRequest::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'otp_hash' => $this->otpService->hashOTP($otp),
                'otp_expires_at' => $expiresAt,
                'otp_attempts' => 0,
                'role_id' => 3, // default Staff
                'verification_status' => 'PENDING',
            ]);

            try {
                Mail::to($userRequest->email)->send(new OTPMail($userRequest->name, $otp, $expiresAt));
                Log::info('OTP sent to: ' . $userRequest->email);
            } catch (\Exception $e) {
                Log::error('Failed to send OTP email: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Check your email for OTP.',
                'step' => 'verify_otp',
                'data' => [
                    'email' => $userRequest->email,
                    'name' => $userRequest->name,
                    'expires_in_minutes' => 10,
                    'verification_status' => 'PENDING',
                    'otp' => env('APP_DEBUG', false) ? $otp : null,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
            ], 500);
        }
    }

    // Verify OTP
    public function verifyOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $result = $this->otpService->verifyOTPRequest($request->email, $request->otp);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'step' => 'verify_otp',
                    'attempts_left' => $result['attempts_left'] ?? null,
                ], $result['code']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Email verified. Wait for admin approval.',
                'step' => 'awaiting_approval',
                'data' => [
                    'email' => $result['data']['email'],
                    'name' => $result['data']['name'],
                    'verification_status' => 'VERIFIED',
                    'account_status' => 'PENDING_APPROVAL',
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('OTP verification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'OTP verification failed',
            ], 500);
        }
    }

    // Resend OTP
    public function resendOTP(Request $request)
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

            $userRequest = $this->otpService->getUserRequest($request->email);
            if (!$userRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'No registration request found',
                ], 404);
            }

            $otp = $this->otpService->generateOTP();
            $userRequest->otp_hash = $this->otpService->hashOTP($otp);
            $userRequest->otp_expires_at = now()->addMinutes(10);
            $userRequest->otp_attempts = 0;
            $userRequest->save();

            try {
                Mail::to($userRequest->email)->send(new OTPMail($userRequest->name, $otp, $userRequest->otp_expires_at));
            } catch (\Exception $e) {
                Log::error('Failed to resend OTP: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP resent. Check your email.',
                'step' => 'verify_otp',
                'data' => [
                    'expires_in_minutes' => 10,
                    'otp' => env('APP_DEBUG', false) ? $otp : null,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Resend OTP error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP',
            ], 500);
        }
    }

    // Check registration status
    public function checkRegistrationStatus(Request $request)
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

            $userRequest = $this->otpService->getUserRequest($request->email);

            if ($userRequest) {
                $statusMap = [
                    'PENDING' => [
                        'message' => 'OTP sent. Verify your email.',
                        'step' => 'verify_otp',
                    ],
                    'VERIFIED' => [
                        'message' => 'Email verified. Wait for admin approval.',
                        'step' => 'awaiting_approval',
                    ],
                    'EXPIRED' => [
                        'message' => 'OTP expired. Request new OTP.',
                        'step' => 'resend_otp',
                    ],
                ];

                $statusInfo = $statusMap[$userRequest->verification_status] ?? [
                    'message' => 'Unknown status',
                    'step' => 'unknown',
                ];

                return response()->json([
                    'success' => true,
                    'message' => $statusInfo['message'],
                    'step' => $statusInfo['step'],
                    'data' => [
                        'email' => $userRequest->email,
                        'name' => $userRequest->name,
                        'verification_status' => $userRequest->verification_status,
                        'created_at' => $userRequest->created_at,
                        'expires_at' => $userRequest->otp_expires_at?->toIso8601String(),
                    ],
                ], 200);
            }

            $user = User::where('email', $request->email)->first();
            if ($user) {
                return response()->json([
                    'success' => true,
                    'message' => $user->status === 'ACTIVE' ? 'Account active. You can login.' : 'Account not active.',
                    'step' => $user->status === 'ACTIVE' ? 'can_login' : 'awaiting_approval',
                    'data' => [
                        'email' => $user->email,
                        'name' => $user->name,
                        'account_status' => $user->status,
                    ],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'No registration request found for this email.',
                'step' => 'not_registered',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Check status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check status',
            ], 500);
        }
    }

    // Reject user request (admin)
    public function rejectUserRequest(Request $request)
    {
        try {
            $admin = $request->user();
            if (!$admin || $admin->role->name !== 'Admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only admin can reject.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'reason' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $userRequest = UserRequest::where('email', $request->email)
                ->where('verification_status', 'VERIFIED')
                ->first();

            if (!$userRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'No verified registration request found.',
                ], 404);
            }

            $userRequest->verification_status = 'REJECTED';
            $userRequest->save();

            try {
                Mail::to($userRequest->email)->send(new AccountRejectedMail(
                    $userRequest->name,
                    $request->reason ?? 'Your registration was rejected by admin.'
                ));
            } catch (\Exception $e) {
                Log::error('Failed to send rejection email: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'User registration rejected.',
                'data' => [
                    'email' => $userRequest->email,
                    'name' => $userRequest->name,
                    'reason' => $request->reason ?? null,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Reject user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject user',
            ], 500);
        }
    }

    // Login
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $result = $this->tokenService->authenticateUser($request->email, $request->password);

            if (!$result['success']) {
                return response()->json([
                    'status' => $result['code'],
                    'message' => $result['message'],
                    'attempts_left' => $result['attempts_left'] ?? null,
                ], $result['code']);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Login successful.',
                'step' => 'logged_in',
                'data' => $result['data'],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Login failed',
            ], 500);
        }
    }

    // Logout
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['status' => 401, 'message' => 'Not authenticated'], 401);
            }

            $this->tokenService->revokeCurrentToken($user->currentAccessToken());

            return response()->json(['status' => 200, 'message' => 'Logged out successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Logout failed'], 500);
        }
    }
}
