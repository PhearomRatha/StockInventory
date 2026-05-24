<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permission  Format: "module.action" e.g. "products.view"
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated.',
                'error_code' => 'NOT_AUTHENTICATED',
            ], 401);
        }

        // Check if user account is inactive
        if ($user->isInactive()) {
            return response()->json([
                'status' => 403,
                'message' => 'Your account has been deactivated. Please contact administrator.',
                'error_code' => 'ACCOUNT_INACTIVE',
            ], 403);
        }

        // Load role relationships if not already loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Check if user has the required permission
        if (!$user->can($permission)) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied. You do not have permission to perform this action.',
                'error_code' => 'INSUFFICIENT_PERMISSIONS',
                'required_permission' => $permission,
                'user_permissions' => $user->getAllPermissions(),
            ], 403);
        }

        return $next($request);
    }
}