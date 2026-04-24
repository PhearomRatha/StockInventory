<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserRequest;
use App\Helpers\ResponseHelper;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    /**
     * Get pending user requests
     */
    public function getPendingRequests()
    {
        try {
            // OPTIMIZED: Add pagination and eager load user relationship
            $pendingRequests = UserRequest::with('user')
                ->where('status', 'pending')
                ->latest()
                ->limit(50)
                ->get();
            
            return ResponseHelper::success('Pending requests retrieved successfully', $pendingRequests);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * FIXED: used with('user') to avoid double query
     * Approve a user
     */
    public function approveUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'role_id' => 'required|exists:roles,id'
            ]);

            // FIXED: Use with('user') to avoid double query
            $userRequest = UserRequest::with('user')->where('user_id', $validated['user_id'])->firstOrFail();
            $user = $userRequest->user;

            $user->status = 'active';
            $user->role_id = $validated['role_id'];
            $user->save();

            $userRequest->status = 'approved';
            $userRequest->save();

            // Clear user cache after status change
            Cache::forget('admin_stats');

            Mail::to($user->email)->send(new AccountApprovedMail($user));

            return ResponseHelper::success('User approved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * FIXED: used with('user') to avoid double query
     * Reject a user
     */
    public function rejectUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'reason' => 'nullable|string'
            ]);

            // FIXED: Use with('user') to avoid double query
            $userRequest = UserRequest::with('user')->where('user_id', $validated['user_id'])->firstOrFail();
            $user = $userRequest->user;

            $user->status = 'rejected';
            $user->save();

            $userRequest->status = 'rejected';
            $userRequest->rejection_reason = $validated['reason'] ?? null;
            $userRequest->save();

            // Clear user cache after status change
            Cache::forget('admin_stats');

            Mail::to($user->email)->send(new AccountRejectedMail($user, $validated['reason'] ?? null));

            return ResponseHelper::success('User rejected successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get all users
     */
    public function getAllUsers(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            
            // OPTIMIZED: Select only needed columns + paginate
            $users = User::select('id', 'name', 'email', 'role_id', 'status', 'created_at')
                ->with(['role:id,name'])  // Only fetch role name
                ->paginate(min($perPage, 100));
            
            return ResponseHelper::success('Users retrieved successfully', $users);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get admin statistics
     */
    public function getStats()
    {
        try {
            // OPTIMIZED: Cache stats for 5 minutes
            $stats = Cache::remember('admin_stats', 300, function () {
                // OPTIMIZED: Use single query with conditional aggregation
                $userStats = User::selectRaw(
                    "
                    COUNT(*) as total_users,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_users
                    "
                )->first();

                $pendingRequests = UserRequest::where('status', 'pending')->count();

                return [
                    'total_users' => $userStats->total_users,
                    'active_users' => $userStats->active_users,
                    'pending_requests' => $pendingRequests,
                    'rejected_users' => $userStats->rejected_users
                ];
            });

            return ResponseHelper::success('Admin stats retrieved successfully', $stats);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Toggle user status
     */
    public function toggleUserStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $user = User::findOrFail($validated['user_id']);
            $user->status = $user->status === 'active' ? 'inactive' : 'active';
            $user->save();

            // Clear cache after status change
            Cache::forget('admin_stats');

            return ResponseHelper::success('User status toggled successfully', ['new_status' => $user->status]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * FIXED: used with('user') to avoid double query
     * Approve user request
     */
    public function approveUserRequest(Request $request)
    {
        try {
            $validated = $request->validate([
                'request_id' => 'required|exists:user_requests,id',
                'role_id' => 'required|exists:roles,id'
            ]);

            // FIXED: Use with('user') to avoid double query
            $userRequest = UserRequest::with('user')->findOrFail($validated['request_id']);
            $user = $userRequest->user;

            $user->status = 'active';
            $user->role_id = $validated['role_id'];
            $user->save();

            $userRequest->status = 'approved';
            $userRequest->save();

            // Clear cache after status change
            Cache::forget('admin_stats');

            Mail::to($user->email)->send(new AccountApprovedMail($user));

            return ResponseHelper::success('User request approved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * FIXED: used with('user') to avoid double query
     * Reject user request
     */
    public function rejectUserRequest(Request $request)
    {
        try {
            $validated = $request->validate([
                'request_id' => 'required|exists:user_requests,id',
                'reason' => 'nullable|string'
            ]);

            // FIXED: Use with('user') to avoid double query
            $userRequest = UserRequest::with('user')->findOrFail($validated['request_id']);
            $user = $userRequest->user;

            $user->status = 'rejected';
            $user->save();

            $userRequest->status = 'rejected';
            $userRequest->rejection_reason = $validated['reason'] ?? null;
            $userRequest->save();

            // Clear cache after status change
            Cache::forget('admin_stats');

            Mail::to($user->email)->send(new AccountRejectedMail($user, $validated['reason'] ?? null));

            return ResponseHelper::success('User request rejected successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
