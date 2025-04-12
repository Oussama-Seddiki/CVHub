<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        Log::info('Registration attempt', [
            'name' => $request->name,
            'email' => $request->email,
            'all_data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            Log::info('Registration validation passed');

            // Test database connection before creating user
            try {
                $connection = \DB::connection()->getPdo();
                Log::info('Database connection successful for registration', [
                    'database' => \DB::connection()->getDatabaseName()
                ]);
            } catch (\Exception $e) {
                Log::error('Database connection error during registration', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            // Create the user using Eloquent
            try {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                Log::info('User created via Eloquent', ['user_id' => $user->id]);

                event(new Registered($user));

                // Try authenticating the user
                try {
                    Auth::login($user);
                    Log::info('User authenticated after registration', ['user_id' => $user->id]);
                } catch (\Exception $e) {
                    Log::error('Failed to authenticate user after registration', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                $redirectRoute = route('dashboard', absolute: false);
                Log::info('Redirecting to', ['route' => $redirectRoute]);
                return redirect($redirectRoute);
            } catch (\Exception $e) {
                Log::error('Failed to create user', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Registration error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
