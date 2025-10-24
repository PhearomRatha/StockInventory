<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
        public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role_id' => 'required|exists:roles,id', // allow user to choose role
            ]);

            $validated['password'] = Hash::make($validated['password']);

            $user = User::create($validated);

            // Generate token immediately after registration
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'status' => 201,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['status'=>500, 'message'=>$e->getMessage()], 500);
        }
    }
    // Login
   public function login(Request $request)
{
    try {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        // Fetch user with role
        $user = User::with('role')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 401, 'message' => 'Invalid credentials'], 401);
        }

        // Generate API token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'message' => 'Login successful',
            'data' => [
                'user' => $user, // full user object with role
                'token' => $token
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
    }
}


    // Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['status'=>200,'message'=>'Logged out successfully']);
    }
}
