<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_subscribed' => false,
            'subscription_status' => 'none'
        ]);

        // Run custom seeders
        $this->call([
            ApiConfigsSeeder::class,
            CategoriesSeeder::class,
            TemplatesSeeder::class,
        ]);
    }
}
