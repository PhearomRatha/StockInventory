<?php

namespace App\Services;

use App\Models\User;
use App\Models\Roles;
use App\Models\UserRequest;
use App\Models\OTPCode;
use App\Helpers\ResponseHelper;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * AdminService
 *
 * Handles all admin-specific operations including:
 * - User approval/rejection workflow
 * - Role management
 * - Permission management
 * - System statistics
 */
class AdminService
{
    /**
     * Get all pending user requests awaiting approval.
     */
    public function getPendingRequests(): array
    {
        return UserRequest::where('status', 'pending')
            ->latest()
            ->limit(100)
            ->get();
    }

    /**
     * Get pending user registrations (users with PENDING status).
     */
    public function getPendingUsers(): array
    {
        return User::where('status', User::STATUS_PENDING)
            ->with('role:id,name')
            ->latest()
            ->limit(100)
            ->get();
    }

    /**
     * Approve a user registration request.
     *
     * Creates the actual User record from the UserRequest.
     */
    public function approveUserRequest(int $requestId, int $roleId): array
    {
        $userRequest = UserRequest::findOrFail($requestId);

        // Verify OTP was verified
        if ($userRequest->verification_status !== 'VERIFIED') {
            return [
                'success' => false,
                'message' => 'OTP must be verified before approval.',
                'code' => 400,
            ];
        }

        // Check if user already exists
        $existingUser = User::where('email', $userRequest->email)->first();
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'A user with this email already exists.',
                'code' => 409,
            ];
        }

        // Create user from the request
        $user = User::create([
            'name' => $userRequest->name,
            'email' => $userRequest->email,
            'password' => $userRequest->password, // Already hashed
            'role_id' => $roleId,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Mark request as approved
        $userRequest->update(['status' => 'approved']);

        // Send approval email
        Mail::to($user->email)->send(new AccountApprovedMail($user));

        // Clear admin stats cache
        Cache::forget('admin_stats');

        return [
            'success' => true,
            'message' => 'User request approved successfully.',
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
        ];
    }

    /**
     * Approve an existing pending user (admin approves a registered user).
     */
    public function approveExistingUser(int $userId): array
    {
        $user = User::findOrFail($userId);

        if ($user->status !== User::STATUS_PENDING) {
            return [
                'success' => false,
                'message' => 'User is not in pending status.',
                'code' => 400,
            ];
        }

        $user->update(['status' => User::STATUS_ACTIVE]);

        // Clear cache
        Cache::forget('admin_stats');

        // Send approval email
        Mail::to($user->email)->send(new AccountApprovedMail($user));

        return [
            'success' => true,
            'message' => 'User approved successfully.',
            'data' => ['user_id' => $user->id],
        ];
    }

    /**
     * Reject a user registration request.
     */
    public function rejectUserRequest(int $requestId, ?string $reason = null): array
    {
        $userRequest = UserRequest::findOrFail($requestId);

        // Delete the user if it was created
        $existingUser = User::where('email', $userRequest->email)->first();
        if ($existingUser) {
            $existingUser->delete();
        }

        $userRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Send rejection email if user exists
        if ($existingUser) {
            Mail::to($existingUser->email)->send(new AccountRejectedMail($existingUser, $reason));
        }

        Cache::forget('admin_stats');

        return [
            'success' => true,
            'message' => 'User request rejected successfully.',
        ];
    }

    /**
     * Reject and deactivate an existing pending user.
     */
    public function rejectExistingUser(int $userId, ?string $reason = null): array
    {
        $user = User::findOrFail($userId);

        $user->update(['status' => User::STATUS_INACTIVE]);

        Cache::forget('admin_stats');
        Mail::to($user->email)->send(new AccountRejectedMail($user, $reason));

        return [
            'success' => true,
            'message' => 'User rejected and deactivated.',
        ];
    }

    /**
     * Get admin dashboard statistics.
     */
    public function getStats(): array
    {
        return Cache::remember('admin_stats', 300, function () {
            $userStats = User::selectRaw("
                COUNT(*) as total_users,
                SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_users,
                SUM(CASE WHEN status = 'INACTIVE' THEN 1 ELSE 0 END) as inactive_users
            ")->first();

            $pendingRequests = UserRequest::where('status', 'pending')->count();
            $totalRoles = Roles::count();
            $totalPermissions = \App\Models\Permission::count();

            return [
                'total_users' => $userStats->total_users,
                'active_users' => $userStats->active_users,
                'pending_users' => $userStats->pending_users,
                'inactive_users' => $userStats->inactive_users,
                'pending_requests' => $pendingRequests,
                'total_roles' => $totalRoles,
                'total_permissions' => $totalPermissions,
            ];
        });
    }

    /**
     * Clear admin stats cache.
     */
    public function clearStatsCache(): void
    {
        Cache::forget('admin_stats');
    }
}