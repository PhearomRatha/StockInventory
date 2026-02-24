<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Helpers\ResponseHelper;

class UserController extends Controller
{
    /**
     * Get all users
     */
    public function index()
    {
        try {
            $users = User::with('role')->get();
            return ResponseHelper::success('Users retrieved successfully', $users);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get user by ID
     */
    public function show($id)
    {
        try {
            $user = User::with('role')->findOrFail($id);
            return ResponseHelper::success('User retrieved successfully', $user);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Update a user
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|max:255|unique:users,email,' . $id,
                'role_id' => 'sometimes|required|exists:roles,id',
                'status' => 'sometimes|required|string'
            ]);

            $user->update($validated);
            return ResponseHelper::success('User updated successfully', $user);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete a user
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return ResponseHelper::success('User deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
