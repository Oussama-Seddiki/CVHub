<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FormatJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the response
        $response = $next($request);
        
        // Make sure it's JSON if it's an API route
        if ($request->is('api/*') || $request->is('api-diagnostic')) {
            // Ensure content type is set to application/json
            $response->header('Content-Type', 'application/json; charset=utf-8');
            
            // If config says to disable output buffering, do so
            if (config('app.api.disable_output_buffering', true)) {
                // Turn off output buffering
                while (ob_get_level()) {
                    ob_end_clean();
                }
            }
        }
        
        return $response;
    }
} 