<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Activity_logs;

class ActivityLogger
{
    public function handle($request, Closure $next, $action = null, $module = null)
    {
        $response = $next($request);

        if ($action && $module && auth()->check()) {
            Activity_logs::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'module' => $module,
                'record_id' => null
            ]);
        }

        return $response;
    }
}
