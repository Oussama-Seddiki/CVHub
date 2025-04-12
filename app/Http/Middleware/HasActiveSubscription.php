<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HasActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return redirect()->route('login');
            }
            
            // Check if user has an active subscription
            $user = Auth::user();
            if (!$user->hasActiveSubscription()) {
                return redirect()->route('subscription')
                    ->with('error', 'يجب عليك الاشتراك أولاً للوصول إلى هذه الميزة.');
            }
            
            return $next($request);
        } catch (\Exception $e) {
            // Log any errors
            Log::error('Subscription middleware error: ' . $e->getMessage());
            
            // In case of error, redirect to subscription page
            return redirect()->route('subscription')
                ->with('error', 'حدث خطأ أثناء التحقق من اشتراكك. يرجى المحاولة مرة أخرى.');
        }
    }
}
