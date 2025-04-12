<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Providers\RouteServiceProvider;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        // Pass the redirect parameter if it exists
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'redirect' => $request->query('redirect')
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        Log::info('Login attempt', [
            'email' => $request->email,
            'redirect' => $request->input('redirect'),
            'session_id' => $request->session()->getId(),
            'session_active' => $request->session()->isStarted()
        ]);
        
        try {
            $request->authenticate();
            $request->session()->regenerate();
            
            Log::info('Login successful', [
                'email' => $request->email, 
                'user_id' => Auth::id(),
                'session_id' => $request->session()->getId(),
                'session_token' => $request->session()->token()
            ]);
            
            // Get intended url from session, fallback to redirect parameter, then dashboard
            $redirectTo = session()->pull('url.intended') 
                ?? $request->input('redirect') 
                ?? $request->query('redirect') 
                ?? RouteServiceProvider::HOME;
            
            Log::info('Redirecting after login', [
                'redirect_to' => $redirectTo
            ]);

            return redirect()->intended($redirectTo);
            
        } catch (\Exception $e) {
            Log::error('Login error', [
                'message' => $e->getMessage(),
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->withErrors([
                'email' => __('auth.failed'),
            ])->onlyInput('email');
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
