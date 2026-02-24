<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserRequest;
use App\Helpers\ResponseHelper;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    /**
     * Get pending user requests
     */
    public function getPendingRequests()
    {
        try {
            $pendingRequests = UserRequest::where('status', 'pending')->get();
            return ResponseHelper::success('Pending requests retrieved successfully', $pendingRequests);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Approve a user
     */
    public function approveUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'role_id' => 'required|exists:roles,id'
            ]);

            $userRequest = UserRequest::where('user_id', $validated['user_id'])->firstOrFail();
            $user = User::findOrFail($validated['user_id']);

            $user->status = 'active';
            $user->role_id = $validated['role_id'];
            $user->save();

            $userRequest->status = 'approved';
            $userRequest->save();

            Mail::to($user->email)->send(new AccountApprovedMail($user));

            return ResponseHelper::success('User approved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Reject a user
     */
    public function rejectUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'reason' => 'nullable|string'
            ]);

            $userRequest = UserRequest::where('user_id', $validated['user_id'])->firstOrFail();
            $user = User::findOrFail($validated['user_id']);

            $user->status = 'rejected';
            $user->save();

            $userRequest->status = 'rejected';
            $userRequest->rejection_reason = $validated['reason'] ?? null;
            $userRequest->save();

            Mail::to($user->email)->send(new AccountRejectedMail($user, $validated['reason'] ?? null));

            return ResponseHelper::success('User rejected successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get all users
     */
    public function getAllUsers()
    {
        try {
            $users = User::with('role')->get();
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
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'pending_requests' => UserRequest::where('status', 'pending')->count(),
                'rejected_users' => User::where('status', 'rejected')->count()
            ];

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

            return ResponseHelper::success('User status toggled successfully', ['new_status' => $user->status]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Approve user request
     */
    public function approveUserRequest(Request $request)
    {
        try {
            $validated = $request->validate([
                'request_id' => 'required|exists:user_requests,id',
                'role_id' => 'required|exists:roles,id'
            ]);

            $userRequest = UserRequest::findOrFail($validated['request_id']);
            $user = User::findOrFail($userRequest->user_id);

            $user->status = 'active';
            $user->role_id = $validated['role_id'];
            $user->save();

            $userRequest->status = 'approved';
            $userRequest->save();

            Mail::to($user->email)->send(new AccountApprovedMail($user));

            return ResponseHelper::success('User request approved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function rejectUserRequest(Request $request)
    {
        try {
            $validated = $request->validate([
                'request_id' => 'required|exists:user_requests,id',
                'reason' => 'nullable|string'
            ]);

            $userRequest = UserRequest::findOrFail($validated['request_id']);
            $user = User::findOrFail($userRequest->user_id);

            $user->status = 'rejected';
            $user->save();

            $userRequest->status = 'rejected';
            $userRequest->rejection_reason = $validated['reason'] ?? null;
            $userRequest->save();

            Mail::to($user->email)->send(new AccountRejectedMail($user, $validated['reason'] ?? null));

            return ResponseHelper::success('User request rejected successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
