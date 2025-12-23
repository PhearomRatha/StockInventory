<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Get all users
    public function index()
    {
        try {
            $users = User::all();
            return response()->json([
                'status' => 200,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
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
            // Remove role_id validation from user input
            'status' => 'nullable|in:pending,active,inactive'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role_id'] = 2;
        $validated['status'] = 'pending';

        $user = User::create($validated);

        // Generate token if needed
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => 201,
            'message' => 'User created successfully',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 201);
    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
    }
}

    // Update user (approve, reject, edit info)
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

            return response()->json([
                'status'=>200,
                'message'=>'User updated successfully',
                'data'=>$user
            ]);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    // Deactivate (set inactive)
    // Permanently remove user
public function destroy($id)
{
    try {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'status' => 200,
            'message' => 'User removed successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
    }
}

}
