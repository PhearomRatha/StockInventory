<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\Roles;
use App\Helpers\ResponseHelper;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    public function getPendingRequests()
    {
        $pendingRequests = UserRequest::with('user')
            ->where('status', 'pending')
            ->latest()
            ->limit(50)
            ->get();
        
        return ResponseHelper::success('Pending requests retrieved successfully', $pendingRequests);
    }

    public function approveUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $userRequest = UserRequest::with('user')->where('user_id', $validated['user_id'])->firstOrFail();
        $user = $userRequest->user;

        $user->update([
            'status' => User::STATUS_ACTIVE,
            'role_id' => $validated['role_id'],
        ]);

        $userRequest->update(['status' => 'approved']);

        Cache::forget('admin_stats');
        Mail::to($user->email)->send(new AccountApprovedMail($user));

        return ResponseHelper::success('User approved successfully');
    }

    public function rejectUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string'
        ]);

        $userRequest = UserRequest::with('user')->where('user_id', $validated['user_id'])->firstOrFail();
        $user = $userRequest->user;

        $user->update(['status' => User::STATUS_INACTIVE]);
        $userRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'] ?? null,
        ]);

        Cache::forget('admin_stats');
        Mail::to($user->email)->send(new AccountRejectedMail($user, $validated['reason'] ?? null));

        return ResponseHelper::success('User rejected successfully');
    }

    public function getAllUsers(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        
        $users = User::select('id', 'name', 'email', 'role_id', 'status', 'created_at')
            ->with(['role:id,name'])
            ->paginate(min($perPage, 100));
        
        return ResponseHelper::success('Users retrieved successfully', $users);
    }

    public function getStats()
    {
        $stats = Cache::remember('admin_stats', 300, function () {
            $userStats = User::selectRaw(
                "COUNT(*) as total_users,
                 SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) as active_users,
                 SUM(CASE WHEN status = 'INACTIVE' THEN 1 ELSE 0 END) as inactive_users"
            )->first();

            $pendingRequests = UserRequest::where('status', 'pending')->count();
            $totalRoles = Roles::count();

            return [
                'total_users' => $userStats->total_users,
                'active_users' => $userStats->active_users,
                'inactive_users' => $userStats->inactive_users,
                'pending_requests' => $pendingRequests,
                'total_roles' => $totalRoles,
            ];
        });

        return ResponseHelper::success('Admin stats retrieved successfully', $stats);
    }

    public function toggleUserStatus(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->update([
            'status' => $user->status === User::STATUS_ACTIVE ? User::STATUS_INACTIVE : User::STATUS_ACTIVE
        ]);

        Cache::forget('admin_stats');

        return ResponseHelper::success('User status toggled successfully', ['new_status' => $user->status]);
    }

    public function approveUserRequest(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'required|exists:user_requests,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $userRequest = UserRequest::with('user')->findOrFail($validated['request_id']);
        $user = $userRequest->user;

        $user->update([
            'status' => User::STATUS_ACTIVE,
            'role_id' => $validated['role_id'],
        ]);

        $userRequest->update(['status' => 'approved']);

        Cache::forget('admin_stats');
        Mail::to($user->email)->send(new AccountApprovedMail($user));

        return ResponseHelper::success('User request approved successfully');
    }

    public function rejectUserRequest(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'required|exists:user_requests,id',
            'reason' => 'nullable|string'
        ]);

        $userRequest = UserRequest::with('user')->findOrFail($validated['request_id']);
        $user = $userRequest->user;

        $user->update(['status' => User::STATUS_INACTIVE]);
        $userRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'] ?? null,
        ]);

        Cache::forget('admin_stats');
        Mail::to($user->email)->send(new AccountRejectedMail($user, $validated['reason'] ?? null));

        return ResponseHelper::success('User request rejected successfully');
    }
}