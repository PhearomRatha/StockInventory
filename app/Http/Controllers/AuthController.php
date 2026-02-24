<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Roles;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Login with email and password
     */
  public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->tokenService
            ->authenticateUser($request->email, $request->password);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Login with Google OAuth
     */
 public function googleLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // IMPORTANT: stateless() for api.php
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->token);

            if (!$googleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google token',
                ], 401);
            }

            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found',
                ], 404);
            }

            if ($user->status !== User::STATUS_ACTIVE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not active',
                ], 403);
            }

            // Save google_id if first login
            if (empty($user->google_id)) {
                $user->update([
                    'google_id' => $googleUser->getId()
                ]);
            }

            // Create Sanctum token
            $token = $user->createToken('auth-token');

            return response()->json([
                'success' => true,
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
                ]
            ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Google Login Error: ' . $errorMessage);
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Determine more specific error message
            $message = 'Google login failed';
            
            if (str_contains($errorMessage, 'invalid_grant') || str_contains($errorMessage, 'Invalid token')) {
                $message = 'Invalid Google token';
            } elseif (str_contains($errorMessage, 'drive.client')) {
                $message = 'Google OAuth not configured properly';
            } elseif (str_contains($errorMessage, 'SQLSTATE')) {
                $message = 'Database error during Google login';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'debug' => [
                    'error' => $errorMessage,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }


   
    /**
     * Redirect to Google for OAuth (redirect-based flow - avoids COOP issues)
     */
    public function googleRedirect(Request $request)
    {
        try {
            $url = Socialite::driver('google')
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'success' => true,
                'url' => $url,
                'type' => 'redirect', // Frontend should redirect to this URL
            ], 200);
        } catch (\Exception $e) {
            Log::error('Google Redirect Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Google redirect URL',
                'debug' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }

    public function googleCallback(Request $request)
    {
        try {
            // IMPORTANT: stateless() for API calls
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find user by Google ID or email
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found. Please contact your administrator to create an account for you.',
                    'error_code' => 'ACCOUNT_NOT_FOUND',
                ], 404);
            }

            if ($user->status !== User::STATUS_ACTIVE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not active. Please contact your administrator.',
                ], 403);
            }

            // Update Google ID if not set
            if (empty($user->google_id)) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            // Create token
            $token = $user->createToken('auth-token', ['*'], now()->addHours(24));

            return response()->json([
                'success' => true,
                'message' => 'Google login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role->name ?? 'User',
                        'role_id' => $user->role_id,
                        'status' => $user->status,
                        'has_password' => $user->hasPassword(),
                    ],
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $token->accessToken->expires_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Google callback error: ' . $errorMessage);
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Determine more specific error message
            $message = 'Google authentication failed';
            
            if (str_contains($errorMessage, 'invalid_grant') || str_contains($errorMessage, 'Invalid token')) {
                $message = 'Invalid Google token';
            } elseif (str_contains($errorMessage, 'drive.client')) {
                $message = 'Google OAuth not configured properly';
            } elseif (str_contains($errorMessage, 'SQLSTATE')) {
                $message = 'Database error during Google authentication';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'debug' => [
                    'error' => $errorMessage,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            $this->tokenService->revokeCurrentToken($user->currentAccessToken());

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }


    public function logoutAll(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            $this->tokenService->revokeAllUserTokens($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Logged out from all devices successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout all error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout from all devices'
            ], 500);
        }
    }

    
   public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        $user->load('role');

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }


  
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password incorrect',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

   
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            // Get current token and revoke it
            $currentToken = $user->currentAccessToken();
            $this->tokenService->revokeCurrentToken($currentToken);

            // Create new token
            $token = $user->createToken('auth-token', ['*'], now()->addHours(24));

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $token->accessToken->expires_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Refresh token error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token'
            ], 500);
        }
    }

    /**
     * Reset user password (Admin only)
     */
    public function adminResetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'new_password' => 'required|string|min:6|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $admin = $request->user();
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            // Check if admin has permission
            if (!$admin->isAdmin() && !$admin->isManager()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized'
                ], 403);
            }

            $targetUser = User::findOrFail($request->user_id);

            // Manager cannot reset Admin or Manager passwords
            if ($admin->isManager()) {
                if ($targetUser->isAdmin() || $targetUser->isManager()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Managers cannot reset passwords of Admin or Manager accounts'
                    ], 403);
                }
            }

            // Reset password
            $targetUser->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Revoke all tokens except current one
            $targetUser->tokens()->where('id', '!=', $targetUser->currentAccessToken()->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully. User will need to log in again.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Admin reset password error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password'
            ], 500);
        }
    }

    /**
     * Get all available roles
     */
    public function getRoles()
    {
        try {
            $roles = Roles::all();
            return response()->json([
                'success' => true,
                'data' => $roles,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get roles error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get roles'
            ], 500);
        }
    }
}
