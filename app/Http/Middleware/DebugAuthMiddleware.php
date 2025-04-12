<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log authentication status before processing
        Log::info('Debug Auth Before', [
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'session_id' => $request->session()->getId(),
            'has_session' => $request->hasSession(),
            'session_active' => $request->session()->isStarted(),
            'session_driver' => config('session.driver'),
            'cookie_name' => config('session.cookie'),
        ]);

        // Process the request
        $response = $next($request);

        // Log authentication status after processing
        Log::info('Debug Auth After', [
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'response_status' => $response->getStatusCode(),
            'session_id' => $request->session()->getId(),
        ]);

        return $response;
    }
}
