<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Activity_logs as ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    // Get all users with role (single endpoint)
    public function index()
    {
        try {
            // Select only needed fields + eager load role
            $users = User::select('id', 'name', 'email', 'status', 'role_id')
                ->with(['role:id,name'])->get();

            return response()->json([
                'status' => 200,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Create new user (Pending by default)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $validated['role_id'] = 2; // default role
            $validated['status'] = 0; // pending

            $user = User::create($validated);

            if (auth()->id()) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'created',
                    'module' => 'users',
                    'record_id' => $user->id
                ]);
            }

            return response()->json([
                'status' => 201,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Update user info or status
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name'=>'sometimes|string|max:255',
                'email'=>'sometimes|email|unique:users,email,'.$id,
                'password'=>'sometimes|nullable|string|min:6',
                'role_id'=>'sometimes|exists:roles,id',
                'status'=>'sometimes|integer|in:0,1,2'
            ]);

            if(isset($validated['password'])){
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            if (auth()->id()) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'updated',
                    'module' => 'users',
                    'record_id' => $user->id
                ]);
            }

            return response()->json([
                'status'=>200,
                'message'=>'User updated successfully',
                'data'=>$user
            ]);

        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    // Permanently remove user
  // Soft delete by changing status
public function destroy($id)
{
    try {
        $user = User::findOrFail($id);

        // Change status instead of deleting
        $user->status = 2; // 2 = deleted/disabled
        $user->save();

        // Log the action
        if (auth()->id()) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'disabled', // instead of 'deleted'
                'module' => 'users',
                'record_id' => $user->id
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'User disabled successfully',
            'data' => $user
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => $e->getMessage()
        ], 500);
    }
}


    // Get total users by role
    public function totalUsers()
    {
        try {
            return Cache::remember('total_users_by_role', 10, function () {
                $usersByRole = User::join('roles', 'users.role_id', '=', 'roles.id')
                    ->select('roles.name as role', DB::raw('count(*) as count'))
                    ->groupBy('roles.name')
                    ->pluck('count', 'role');

                return response()->json([
                    'status' => 200,
                    'total_admin' => $usersByRole['admin'] ?? 0,
                    'total_manager' => $usersByRole['manager'] ?? 0,
                    'total_staff' => $usersByRole['staff'] ?? 0,
                    'total_users' => $usersByRole->sum()
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}
