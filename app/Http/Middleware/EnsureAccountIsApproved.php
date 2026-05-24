<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAccountIsApproved
{
    /**
     * Handle an incoming request.
     * Blocks access for inactive users, even if authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated.',
                'error_code' => 'NOT_AUTHENTICATED',
            ], 401);
        }

        if ($user->isInactive()) {
            return response()->json([
                'status' => 403,
                'message' => 'Your account has been deactivated. Please contact administrator.',
                'error_code' => 'ACCOUNT_INACTIVE',
            ], 403);
        }

        return $next($request);
    }
}