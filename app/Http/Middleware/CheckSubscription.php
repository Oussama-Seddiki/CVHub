<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من وجود مستخدم مسجل الدخول
        if (Auth::check()) {
            $user = Auth::user();
            
            // التحقق من وجود اشتراك نشط
            if (!$user->hasActiveSubscription()) {
                // إعادة توجيه المستخدم إلى صفحة الاشتراك
                return redirect()->route('subscription')
                    ->with('error', 'يجب عليك الاشتراك أولاً للوصول إلى هذه الميزة.');
            }
        }
        
        return $next($request);
    }
}
