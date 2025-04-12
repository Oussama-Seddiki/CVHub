<?php

namespace Database\Seeders;

use App\Models\ApiConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApiConfigsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resumaker AI API
        ApiConfig::create([
            'service' => 'resumaker_ai',
            'api_key' => 'demo_key_resumaker',
            'api_secret' => 'demo_secret_resumaker',
            'base_url' => 'https://api.resumaker.ai/v1',
            'additional_config' => json_encode([
                'timeout' => 60,
                'features' => ['pdf', 'docx', 'ai_enhancement']
            ]),
            'is_active' => true
        ]);

        // ilovepdf API
        ApiConfig::create([
            'service' => 'ilovepdf',
            'api_key' => 'demo_key_ilovepdf',
            'api_secret' => 'demo_secret_ilovepdf',
            'base_url' => 'https://api.ilovepdf.com/v1',
            'additional_config' => json_encode([
                'timeout' => 120,
                'max_file_size' => 50 * 1024 * 1024, // 50MB
                'features' => ['compress', 'merge', 'split', 'convert', 'protect']
            ]),
            'is_active' => true
        ]);

        // Scribd API
        ApiConfig::create([
            'service' => 'scribd',
            'api_key' => 'demo_key_scribd',
            'api_secret' => 'demo_secret_scribd',
            'base_url' => 'https://api.scribd.com/v1',
            'additional_config' => json_encode([
                'timeout' => 30,
                'features' => ['upload', 'embed', 'view', 'download']
            ]),
            'is_active' => true
        ]);
    }
}
