<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ---------------- Register ----------------
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);

            // Hash password before storing
            $validated['password'] = Hash::make($validated['password']);

            // Assign default role and inactive status
            $validated['role_id'] = 2; // default role
            $validated['status'] = 0;  // inactive, waiting approval

            $user = User::create($validated);

            return response()->json([
                'status' => 201,
                'message' => 'Registration successful! Waiting for admin approval.',
                'data' => ['user' => $user] // return created user
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['status'=>500, 'message'=>$e->getMessage()], 500);
        }
    }

    // ---------------- Login ----------------
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            // Fetch user by email
            $user = User::with('role')->where('email', $request->email)->first();

            // Check credentials
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['status' => 401, 'message' => 'Invalid credentials'], 401);
            }

            // Check if account is approved
            if ($user->status != 1) {
                return response()->json(['status' => 403, 'message' => 'Your account is not yet approved by admin.'], 403);
            }
            // access token : 
            // refresh token


            $token = $user->createToken('email')->plainTextToken;

            return response()->json([
                'status' => 200,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------- Logout ----------------
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['status'=>200,'message'=>'Logged out successfully']);
    }

}
