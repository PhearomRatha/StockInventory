<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
                'error_code' => 'NOT_AUTHENTICATED'
            ], 401);
        }

        // Check if user account is active
        if ($user->status !== User::STATUS_ACTIVE) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied. Your account is not active.',
                'error_code' => 'ACCOUNT_INACTIVE',
                'required_roles' => $roles
            ], 403);
        }

        // Load the role relation if it's not already loaded
        if ($user && !$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Check if user has a role
        if (!$user->role) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied. Your account has no role assigned.',
                'error_code' => 'NO_ROLE_ASSIGNED',
                'required_roles' => $roles
            ], 403);
        }

        // Check if user has one of the allowed roles
        if (!in_array($user->role->name, $roles)) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied. You do not have permission to access this resource.',
                'error_code' => 'INSUFFICIENT_PERMISSIONS',
                'user_role' => $user->role->name,
                'required_roles' => $roles
            ], 403);
        }

        return $next($request);
    }
}
