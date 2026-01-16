<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Activity_logs as ActivityLog;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Get all users with role (single endpoint)
    public function index()
    {
        try {
            // Select only needed fields + eager load role
            $users = User::select('id', 'name', 'email', 'status', 'role_id')
                ->with(['role:id,name'])
                ->paginate(12);

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

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'created',
                'module' => 'users',
                'record_id' => $user->id
            ]);

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
                'name'=>'sometimes|required|string|max:255',
                'email'=>'sometimes|required|email|unique:users,email,'.$id,
                'password'=>'sometimes|nullable|string|min:6',
                'role_id'=>'sometimes|required|exists:roles,id',
                'status'=>'sometimes|required|integer|in:0,1,2'
            ]);

            if(isset($validated['password'])){
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'module' => 'users',
                'record_id' => $user->id
            ]);

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
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'deleted',
                'module' => 'users',
                'record_id' => $user->id
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'User removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}
