<?php

namespace App\Http\Controllers;

use App\Mail\AccountApprovedMail;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\Roles;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminController extends Controller
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * ==================== GET PENDING REQUESTS ====================
     * Get all verified user requests waiting for admin approval
     */
    public function getPendingRequests(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $perPage = $request->per_page ?? 10;
            $search = $request->search;

            $query = UserRequest::where('verification_status', 'VERIFIED')
                ->orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $requests = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'message' => 'Pending requests retrieved successfully',
                'data' => [
                    'requests' => $requests->items(),
                    'pagination' => [
                        'current_page' => $requests->currentPage(),
                        'last_page' => $requests->lastPage(),
                        'total' => $requests->total(),
                        'per_page' => $requests->perPage(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get pending requests error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve pending requests: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== APPROVE USER ====================
     * Admin approves a verified user request
     * Moves data from user_requests to users table
     */
    public function approveUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_request_id' => 'required|integer|exists:user_requests,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $userRequest = UserRequest::findOrFail($request->user_request_id);

            if ($userRequest->verification_status !== 'VERIFIED') {
                return response()->json([
                    'status' => 400,
                    'message' => 'User request must be verified before approval.',
                ], 400);
            }

            // Create user from request data
            $user = User::create([
                'name' => $userRequest->name,
                'email' => $userRequest->email,
                'password' => $userRequest->password,
                'role_id' => $userRequest->role_id,
                'status' => 'ACTIVE',
            ]);

            // Delete the request record
            $userRequest->delete();

            // Send approval email
            try {
                Mail::to($user->email)->send(new AccountApprovedMail($user->name, $user->email));
                Log::info('Account approval email sent to: ' . $user->email);
            } catch (\Exception $e) {
                Log::error('Failed to send approval email: ' . $e->getMessage());
            }

            Log::info("User approved: {$user->email} by admin {$request->user()->email}");

            return response()->json([
                'status' => 200,
                'message' => 'User approved successfully! Account is now active.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role_id' => $user->role_id,
                        'status' => $user->status,
                        'created_at' => $user->created_at,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Approve user error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to approve user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== REJECT USER ====================
     * Admin rejects a user request (marks as INACTIVE)
     */
    public function rejectUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_request_id' => 'required|integer|exists:user_requests,id',
                'reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $userRequest = UserRequest::findOrFail($request->user_request_id);

            // Store rejection info in a separate table or log (optional)
            Log::warning("User request rejected: {$userRequest->email} by admin {$request->user()->email}. Reason: " . ($request->reason ?? 'No reason provided'));

            // Delete the request (or mark as rejected if you want to keep records)
            $userRequest->delete();

            return response()->json([
                'status' => 200,
                'message' => 'User request rejected successfully.',
                'data' => [
                    'rejected_email' => $userRequest->email,
                    'rejected_at' => now(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Reject user error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to reject user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== GET ALL USERS ====================
     * Get all registered users with pagination and filtering
     */
    public function getAllUsers(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer|min:1|max:100',
                'status' => 'nullable|in:PENDING,ACTIVE,INACTIVE',
                'role' => 'nullable|exists:roles,id',
                'search' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $perPage = $request->per_page ?? 10;
            $status = $request->status;
            $roleId = $request->role;
            $search = $request->search;

            $query = User::with('role')->orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            if ($roleId) {
                $query->where('role_id', $roleId);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'message' => 'Users retrieved successfully',
                'data' => [
                    'users' => $users->items(),
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'total' => $users->total(),
                        'per_page' => $users->perPage(),
                    ],
                    'stats' => [
                        'total' => User::count(),
                        'active' => User::where('status', 'ACTIVE')->count(),
                        'pending' => User::where('status', 'PENDING')->count(),
                        'inactive' => User::where('status', 'INACTIVE')->count(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get all users error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve users: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== GET USER STATS ====================
     * Get statistics for admin dashboard
     */
    public function getStats(Request $request)
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'ACTIVE')->count(),
                'pending_users' => User::where('status', 'PENDING')->count(),
                'inactive_users' => User::where('status', 'INACTIVE')->count(),
                'pending_requests' => UserRequest::where('verification_status', 'VERIFIED')->count(),
                'recent_signups' => User::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            ];

            return response()->json([
                'status' => 200,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get stats error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ==================== TOGGLE USER STATUS ====================
     * Activate or deactivate an existing user
     */
    public function toggleUserStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'status' => 'required|in:ACTIVE,INACTIVE',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::findOrFail($request->user_id);
            $oldStatus = $user->status;
            $newStatus = $request->status;

            // Don't allow deactivating the current admin
            if ($user->id === $request->user()->id && $newStatus === 'INACTIVE') {
                return response()->json([
                    'status' => 400,
                    'message' => 'You cannot deactivate your own account.',
                ], 400);
            }

            $user->status = $newStatus;
            $user->save();

            // Revoke all user tokens if deactivating
            if ($newStatus === 'INACTIVE') {
                $this->tokenService->revokeAllUserTokens($user->id);
            }

            Log::info("User status changed: {$user->email} from {$oldStatus} to {$newStatus} by admin {$request->user()->email}");

            return response()->json([
                'status' => 200,
                'message' => "User status changed to {$newStatus}",
                'data' => [
                    'user_id' => $user->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Toggle user status error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to toggle user status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
