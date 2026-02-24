<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CrossOriginOpenerPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Allow cross-origin opener policy for OAuth flows
        // This is needed for Google OAuth popup to communicate with the parent window
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        
        // Additional header for cross-origin embedder policy (needed for some OAuth flows)
        // Using 'require-corp' would require all resources to have CORS headers
        // Setting to null/empty allows the page to be embedded in cross-origin contexts
        $response->headers->set('Cross-Origin-Embedder-Policy', 'unsafe-none');
        
        // Allow cross-origin resource sharing for API calls
        $response->headers->set('Access-Control-Allow-Origin', '*');
        
        return $response;
    }
}
