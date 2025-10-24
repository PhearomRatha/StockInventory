<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Get all users (Admin only)
    public function index()
    {
        try {
            $users = User::all();
            return response()->json([
                'status' => 200,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    // Create user and generate token
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'=>'required|string|max:255',
                'email'=>'required|email|unique:users,email',
                'password'=>'required|string|min:6',
                'role_id'=>'required|exists:roles,id',
                'status'=>'nullable|in:active,inactive'
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $user = User::create($validated);

            // Generate token for the new user
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'status'=>201,
                'message'=>'User created successfully',
                'data'=>[
                    'user'=>$user,
                    'token'=>$token
                ]
            ],201);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    // Update user
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name'=>'sometimes|required|string|max:255',
                'email'=>'sometimes|required|email|unique:users,email,'.$id,
                'password'=>'sometimes|nullable|string|min:6',
                'role_id'=>'sometimes|required|exists:roles,id',
                'status'=>'sometimes|required|in:active,inactive'
            ]);

            if(isset($validated['password'])){
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'status'=>200,
                'message'=>'User updated successfully',
                'data'=>$user
            ],200);

        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    // Delete user
public function destroy($id)
{
    try {
        $user = User::findOrFail($id);

        // Set user status to inactive instead of deleting
        $user->update(['status' => 0]); // 0 = Inactive

        return response()->json([
            'status' => 200,
            'message' => 'User set to inactive successfully',
            'data' => $user
        ], 200);

    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
    }
}



}
