<?php

namespace App\Http\Controllers;

use App\Mail\OTPMail;
use App\Mail\AccountApprovedMail;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\Roles;
use App\Services\OTPService;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    protected $otpService;
    protected $tokenService;

    public function __construct(OTPService $otpService, TokenService $tokenService)
    {
        $this->otpService = $otpService;
        $this->tokenService = $tokenService;
    }

    /**
     * ==================== REGISTRATION ====================
     * Step 1: User registers with name, email, password
     * Creates OTP request and sends OTP to email
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|email|max:255|unique:users,email|unique:user_requests,email',
                'password' => 'required|string|min:6|max:50|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // Create OTP request
            $userRequest = $this->otpService->createOTPRequest(
                $validated['name'],
                $validated['email'],
                $validated['password'],
                2 // Default role: User
            );

            // Generate OTP and send email
            $otp = $this->otpService->generateOTP();
            $expiresAt = Carbon::now()->addMinutes(10);
            
            $userRequest->otp_hash = $this->otpService->hashOTP($otp);
            $userRequest->otp_expires_at = $expiresAt;
            $userRequest->save();

            // Send OTP email
            try {
                Mail::to($userRequest->email)->send(new OTPMail($userRequest->name, $otp, $expiresAt));
                Log::info('OTP sent to: ' . $userRequest->email);
            } catch (\Exception $e) {
                Log::error('Failed to send OTP email: ' . $e->getMessage());
                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to send OTP email. Please try again.',
                ], 500);
            }

            return response()->json([
                'status' => 201,
                'message' => 'Registration successful! OTP sent to your email. Please verify.',
                'step' => 'verification_pending',
                'data' => [
                    'email' => $userRequest->email,
                    'name' => $userRequest->name,
                    'expires_at' => $expiresAt,
                    'verification_status' => 'PENDING',
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== OTP VERIFICATION ====================
     * Step 2: User enters OTP to verify email
     */
    public function verifyOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $result = $this->otpService->verifyOTPRequest(
                $request->email,
                $request->otp
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => $result['code'],
                    'message' => $result['message'],
                    'attempts_left' => $result['attempts_left'] ?? null,
                ], $result['code']);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Your account is verified. Waiting for admin approval.',
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
                'status' => 500,
                'message' => 'OTP verification failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== RESEND OTP ====================
     * Resend OTP with rate limiting and max attempts
     */
    public function resendOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $result = $this->otpService->resendOTP($request->email);

            if (!$result['success']) {
                return response()->json([
                    'status' => $result['code'],
                    'message' => $result['message'],
                ], $result['code']);
            }

            return response()->json([
                'status' => 200,
                'message' => 'OTP resent successfully! Please check your email.',
                'step' => 'verification_pending',
                'data' => [
                    'expires_at' => $result['data']['expires_at'],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Resend OTP error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to resend OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== CHECK REGISTRATION STATUS ====================
     * Check current status of registration request
     */
    public function checkRegistrationStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check user_requests first
            $userRequest = $this->otpService->getUserRequest($request->email);
            
            if ($userRequest) {
                $statusMessages = [
                    'PENDING' => 'OTP sent. Please verify your email.',
                    'VERIFIED' => 'Your account is verified. Waiting for admin approval.',
                    'EXPIRED' => 'OTP expired. Please request a new OTP.',
                ];

                return response()->json([
                    'status' => 200,
                    'message' => $statusMessages[$userRequest->verification_status] ?? 'Unknown status',
                    'step' => $userRequest->verification_status === 'VERIFIED' ? 'awaiting_approval' : 'verification_pending',
                    'data' => [
                        'email' => $userRequest->email,
                        'name' => $userRequest->name,
                        'verification_status' => $userRequest->verification_status,
                        'is_verified' => $userRequest->verification_status === 'VERIFIED',
                        'created_at' => $userRequest->created_at,
                        'expires_at' => $userRequest->otp_expires_at,
                    ],
                ], 200);
            }

            // Check if user already exists
            $user = User::where('email', $request->email)->first();
            
            if ($user) {
                $statusMessages = [
                    'PENDING' => 'Your account is pending.',
                    'ACTIVE' => 'Your account is active. You can log in.',
                    'INACTIVE' => 'Your account has been deactivated. Please contact administrator.',
                ];

                return response()->json([
                    'status' => 200,
                    'message' => $statusMessages[$user->status] ?? 'Unknown status',
                    'step' => $user->status === 'ACTIVE' ? 'can_login' : ($user->status === 'PENDING' ? 'awaiting_approval' : 'login_blocked'),
                    'data' => [
                        'email' => $user->email,
                        'name' => $user->name,
                        'account_status' => $user->status,
                        'is_active' => $user->status === 'ACTIVE',
                    ],
                ], 200);
            }

            return response()->json([
                'status' => 404,
                'message' => 'No registration request found for this email.',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Check status error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to check status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== LOGIN ====================
     * Only ACTIVE users can log in
     */
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

            $result = $this->tokenService->authenticateUser(
                $request->email,
                $request->password
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => $result['code'],
                    'message' => $result['message'],
                    'attempts_left' => $result['attempts_left'] ?? null,
                    'locked_until' => $result['locked_until'] ?? null,
                ], $result['code']);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Login successful! Welcome back.',
                'step' => 'logged_in',
                'data' => $result['data'],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Login failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== LOGOUT ====================
     * Revoke JWT token to prevent misuse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Not authenticated',
                ], 401);
            }

            // Revoke current token
            $this->tokenService->revokeCurrentToken($request->user()->currentAccessToken());

            return response()->json([
                'status' => 200,
                'message' => 'Logged out successfully. Your session has been terminated.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Logout failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== LOGOUT ALL DEVICES ====================
     * Revoke all tokens for the user
     */
    public function logoutAll(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Not authenticated',
                ], 401);
            }

            // Revoke all tokens
            $count = $this->tokenService->revokeAllUserTokens($user->id);

            return response()->json([
                'status' => 200,
                'message' => "Logged out from all devices. {$count} sessions terminated.",
            ], 200);

        } catch (\Exception $e) {
            Log::error('Logout all error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to logout from all devices: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== GET CURRENT USER ====================
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user()->load('role');
            
            if (!$user) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Not authenticated',
                ], 401);
            }

            $statusDescriptions = [
                'PENDING' => 'Your account is pending verification or approval.',
                'ACTIVE' => 'Your account is fully active.',
                'INACTIVE' => 'Your account has been deactivated.',
            ];

            return response()->json([
                'status' => 200,
                'message' => $statusDescriptions[$user->status] ?? 'Unknown status',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->name ?? 'User',
                    'role_id' => $user->role_id,
                    'status' => $user->status,
                    'is_active' => $user->status === 'ACTIVE',
                    'created_at' => $user->created_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get user error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get user info: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== REFRESH TOKEN ====================
     * Generate new token while keeping current session
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Not authenticated',
                ], 401);
            }

            // Create new token
            $token = $user->createToken('auth-token-refresh', ['*'], now()->addHours(24));
            
            // Store token for tracking
            \App\Models\TokenRevocation::create([
                'token_id' => $token->accessToken->id,
                'user_id' => $user->id,
                'expires_at' => $token->accessToken->expires_at,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $token->accessToken->expires_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Refresh token error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to refresh token: ' . $e->getMessage(),
            ], 500);
        }
    }
}
