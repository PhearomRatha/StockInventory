<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Login
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email'=>'required|email',
                'password'=>'required|string'
            ]);

            $user = User::with('role')->where('email', $request->email)->first();

            if(!$user || !Hash::check($request->password, $user->password)){
                return response()->json(['status'=>401,'message'=>'Invalid credentials'],401);
            }

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'status'=>200,
                'message'=>'Login successful',
                'token'=>$token,
                'user'=>$user
            ]);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['status'=>200,'message'=>'Logged out successfully']);
    }
}
