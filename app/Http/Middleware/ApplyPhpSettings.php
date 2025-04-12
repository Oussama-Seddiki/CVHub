<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyPhpSettings
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get PHP settings from config
        $settings = config('app.php_settings', []);
        
        // Apply PHP settings
        foreach ($settings as $key => $value) {
            if ($value !== null && $value !== '') {
                ini_set($key, $value);
                
                // For max_execution_time also use set_time_limit
                if ($key === 'max_execution_time') {
                    set_time_limit((int)$value);
                }
            }
        }
        
        return $next($request);
    }
} 