<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FeatureAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature = null): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // If no specific feature is specified, default to requiring subscription
        if (!$feature) {
            if (!$user->isSubscribed()) {
                return redirect()->route('subscription')
                    ->with('error', 'يجب أن يكون لديك اشتراك نشط للوصول إلى هذه الميزة');
            }
            return $next($request);
        }
        
        // Check if user can access the specific feature
        if (!$user->canAccessFeature($feature)) {
            return redirect()->route('subscription')
                ->with('error', 'يجب أن يكون لديك اشتراك نشط للوصول إلى هذه الميزة');
        }
        
        return $next($request);
    }
}
