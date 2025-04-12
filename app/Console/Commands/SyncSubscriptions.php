<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all users subscription statuses from their subscription records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting subscription sync...');
        
        // Get count of users
        $count = \App\Models\User::count();
        $this->info("Found {$count} users to process");
        
        // Create a progress bar
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        // Process users in chunks to avoid memory issues
        \App\Models\User::chunk(100, function ($users) use ($bar) {
            foreach ($users as $user) {
                // Update the user's subscription status
                $oldStatus = $user->subscription_status;
                $wasSubscribed = $user->is_subscribed;
                
                $user->updateSubscriptionStatus();
                
                // If status changed, log it
                if ($oldStatus !== $user->subscription_status || $wasSubscribed !== $user->is_subscribed) {
                    $this->line('');
                    $this->info("User #{$user->id} ({$user->email}) status changed: {$oldStatus} -> {$user->subscription_status}");
                }
                
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->line('');
        $this->info('Subscription sync completed!');
        
        return self::SUCCESS;
    }
}
