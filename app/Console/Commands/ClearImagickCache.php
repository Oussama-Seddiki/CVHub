<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearImagickCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imagick:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Imagick cache to prevent memory issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing Imagick cache...');
        
        try {
            // Create a new Imagick instance to access static methods
            $imagick = new \Imagick();
            
            // Get resource limits before clearing
            $memoryBefore = $imagick->getResourceLimit(\Imagick::RESOURCETYPE_MEMORY);
            $diskBefore = $imagick->getResourceLimit(\Imagick::RESOURCETYPE_DISK);
            
            // Clear ImageMagick cache using command line
            if (PHP_OS_FAMILY === 'Windows') {
                $result = shell_exec('magick -cache clear 2>&1');
            } else {
                $result = shell_exec('convert -cache clear 2>&1');
            }
            
            // Force garbage collection
            gc_collect_cycles();
            
            // Set resource limits
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256); // 256MB
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_DISK, 1024); // 1GB
            
            // Get resource limits after clearing
            $memoryAfter = $imagick->getResourceLimit(\Imagick::RESOURCETYPE_MEMORY);
            $diskAfter = $imagick->getResourceLimit(\Imagick::RESOURCETYPE_DISK);
            
            // Log the results
            Log::info('Imagick cache cleared', [
                'memory_before' => $memoryBefore . 'MB',
                'memory_after' => $memoryAfter . 'MB',
                'disk_before' => $diskBefore . 'MB',
                'disk_after' => $diskAfter . 'MB',
                'command_result' => $result
            ]);
            
            // Clean up temporary directory
            $this->cleanupTempDirectories();
            
            $this->info('Imagick cache cleared successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to clear Imagick cache: ' . $e->getMessage());
            Log::error('Failed to clear Imagick cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
    
    /**
     * Clean up temporary directories
     */
    protected function cleanupTempDirectories()
    {
        $tempPath = storage_path('app/temp/pdf_images_*');
        $oldDirectories = glob($tempPath);
        
        $count = 0;
        foreach ($oldDirectories as $dir) {
            if (is_dir($dir) && filemtime($dir) < time() - 3600) { // Older than 1 hour
                // Delete all files in directory
                foreach (glob($dir . '/*') as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                
                // Remove directory
                if (rmdir($dir)) {
                    $count++;
                }
            }
        }
        
        $this->info("Cleaned up $count old temporary directories.");
    }
}
