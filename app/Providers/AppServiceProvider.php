<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use App\Models\Subscription;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the TemporaryStorage service
        $this->app->singleton(\App\Services\Storage\TemporaryStorage::class, function ($app) {
            $basePath = storage_path('app/temp');
            $lifetime = config('app.temp_file_lifetime', 24 * 60); // 24 hours in minutes
            return new \App\Services\Storage\TemporaryStorage($basePath, $lifetime);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Route model binding moved to SubscriptionServiceProvider
        
        Vite::prefetch(concurrency: 3);

        Inertia::share([
            'auth' => function () {
                return [
                    'user' => Auth::user() ? [
                        'id' => Auth::user()->id,
                        'name' => Auth::user()->name,
                        'email' => Auth::user()->email,
                        'hasActiveSubscription' => Auth::user()->hasActiveSubscription(),
                    ] : null,
                ];
            },
            'flash' => function () {
                return [
                    'success' => Session::get('success'),
                    'error' => Session::get('error'),
                ];
            },
        ]);
    }
}
