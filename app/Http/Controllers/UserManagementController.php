<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    /**
     * Get all users (Admin and Manager)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // Check permissions
            if (!$user->isAdmin() && !$user->isManager()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized to view users'
                ], 403);
            }

            $users = User::with('role', 'createdBy')->get();

            return response()->json([
                'success' => true,
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get users error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get users'
            ], 500);
        }
    }

    /**
     * Get a specific user
     */
    public function show(Request $request, $id)
    {
        try {
            $currentUser = $request->user();

            // Check permissions
            if (!$currentUser->isAdmin() && !$currentUser->isManager()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized to view users'
                ], 403);
            }

            $user = User::with('role', 'createdBy')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user'
            ], 500);
        }
    }

    /**
     * Create a new user (Admin and Manager)
     */
    public function store(Request $request)
    {
        try {
            $currentUser = $request->user();

            // Check if user is Admin or Manager
            if (!$currentUser->isAdmin() && !$currentUser->isManager()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized to create users'
                ], 403);
            }

            // Validation rules
            $rules = [
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'nullable|string|min:6|max:50',
            ];

            // If Admin, they can specify role_id
            // If Manager, role_id must be Staff (or not provided)
            if ($currentUser->isAdmin()) {
                $rules['role_id'] = 'sometimes|required|exists:roles,id';
            } elseif ($currentUser->isManager()) {
                // Manager can only create Staff
                $request->merge(['role_id' => $this->getStaffRoleId()]);
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Get role_id
            $roleId = $data['role_id'] ?? $this->getStaffRoleId();

            // Verify Manager can only create Staff
            if ($currentUser->isManager()) {
                $role = Roles::find($roleId);
                if (!$role || $role->name !== User::ROLE_STAFF) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Managers can only create Staff users'
                    ], 403);
                }
            }

            // Verify Admin can only create Admin, Manager, or Staff
            if ($currentUser->isAdmin()) {
                $role = Roles::find($roleId);
                if (!$role || !in_array($role->name, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid role specified'
                    ], 422);
                }
            }

            // Create user
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => User::STATUS_ACTIVE,
                'role_id' => $roleId,
                'created_by' => $currentUser->id,
                'email_verified_at' => now(), // Auto-verify since created by admin
            ];

            // Set password if provided
            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $user = User::create($userData);

            // Load role
            $user->load('role');

            Log::info("User {$currentUser->name} (ID: {$currentUser->id}) created user {$user->name} (ID: {$user->id}) with role {$user->role->name}");

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->name,
                    'role_id' => $user->role_id,
                    'status' => $user->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Create user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a user (Admin only)
     */
    public function update(Request $request, $id)
    {
        try {
            $currentUser = $request->user();

            // Only Admin can update users
            if (!$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can update users'
                ], 403);
            }

            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|max:255|unique:users,email,' . $id,
                'role_id' => 'sometimes|required|exists:roles,id',
                'status' => 'sometimes|required|in:ACTIVE,INACTIVE',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // If role_id is being updated, validate the new role
            if (isset($data['role_id'])) {
                $role = Roles::find($data['role_id']);
                if (!$role || !in_array($role->name, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid role specified'
                    ], 422);
                }
            }

            $user->update($data);
            $user->load('role');

            Log::info("Admin {$currentUser->name} (ID: {$currentUser->id}) updated user {$user->name} (ID: {$user->id})");

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user'
            ], 500);
        }
    }

    /**
     * Delete a user (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $currentUser = $request->user();

            // Only Admin can delete users
            if (!$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can delete users'
                ], 403);
            }

            // Cannot delete yourself
            if ($currentUser->id == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 400);
            }

            $user = User::findOrFail($id);

            $userName = $user->name;
            $user->delete();

            Log::info("Admin {$currentUser->name} (ID: {$currentUser->id}) deleted user {$userName} (ID: {$id})");

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Delete user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user'
            ], 500);
        }
    }

    /**
     * Toggle user status (Admin and Manager)
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $currentUser = $request->user();

            // Only Admin and Manager can toggle status
            if (!$currentUser->isAdmin() && !$currentUser->isManager()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized'
                ], 403);
            }

            $user = User::findOrFail($id);

            // Manager cannot toggle Admin or Manager status
            if ($currentUser->isManager() && ($user->isAdmin() || $user->isManager())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Managers cannot toggle Admin or Manager accounts'
                ], 403);
            }

            $newStatus = $user->status === User::STATUS_ACTIVE ? User::STATUS_INACTIVE : User::STATUS_ACTIVE;
            $user->update(['status' => $newStatus]);

            Log::info("User {$currentUser->name} (ID: {$currentUser->id}) toggled status of user {$user->name} (ID: {$user->id}) to {$newStatus}");

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'status' => $user->status,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Toggle user status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle user status'
            ], 500);
        }
    }

    /**
     * Get the Staff role ID
     */
    private function getStaffRoleId(): int
    {
        $staffRole = Roles::where('name', User::ROLE_STAFF)->first();
        return $staffRole ? $staffRole->id : 3; // Default to 3 if not found
    }
}
