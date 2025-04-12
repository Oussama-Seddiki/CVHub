<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تأكد من أن المستخدم مسجل الدخول
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        // تحقق من وجود اشتراك نشط
        $user = Auth::user();
        if (!$user->isSubscribed()) {
            return redirect()->route('subscription')
                ->with('error', 'يجب أن يكون لديك اشتراك نشط للوصول إلى هذه الميزة');
        }
        
        return $next($request);
    }
} 