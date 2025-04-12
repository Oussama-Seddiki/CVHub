<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'service',
        'api_key',
        'api_secret',
        'base_url',
        'additional_config',
        'is_active'
    ];

    protected $casts = [
        'additional_config' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get an API configuration by service name
     */
    public static function getConfig(string $service)
    {
        return static::where('service', $service)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if an API service is configured and active
     */
    public static function isConfigured(string $service): bool
    {
        return static::where('service', $service)
            ->where('is_active', true)
            ->exists();
    }
}
