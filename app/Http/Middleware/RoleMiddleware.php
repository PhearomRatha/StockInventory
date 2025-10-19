<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // Load the role relation if it’s not already loaded
        if ($user && !$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Check role
        if (!$user || !$user->role || !in_array($user->role->name, $roles)) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied. No roles assigned or invalid user.'
            ], 403);
        }

        return $next($request);
    }
}
