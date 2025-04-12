<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\Subscription;

class SubscriptionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // No need to register middleware as a container binding
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register route model binding
        Route::model('subscription', Subscription::class);
    }
}
