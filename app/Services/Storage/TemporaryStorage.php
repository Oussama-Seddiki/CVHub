<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TemporaryStorage
{
    /**
     * Base directory for temporary files
     * 
     * @var string
     */
    protected $basePath;
    
    /**
     * Time to keep files in minutes
     * 
     * @var int
     */
    protected $lifetime;
    
    /**
     * Constructor
     * 
     * @param string $basePath Base path for temporary storage
     * @param int $lifetime Lifetime in minutes
     */
    public function __construct(string $basePath = null, int $lifetime = 60)
    {
        $this->basePath = $basePath ?? storage_path('app/temp');
        $this->lifetime = $lifetime;
        
        // Create directory if it doesn't exist
        if (!file_exists($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }
    
    /**
     * Store an uploaded file
     * 
     * @param UploadedFile $file
     * @param string $filename Custom filename (optional)
     * @return string Full path to the stored file
     */
    public function store(UploadedFile $file, string $filename = null): string
    {
        $filename = $filename ?? Str::random(40) . '.' . $file->getClientOriginalExtension();
        $path = $this->basePath . '/' . $filename;
        
        // Move the uploaded file
        $file->move($this->basePath, $filename);
        
        return $path;
    }
    
    /**
     * Store file contents
     * 
     * @param string $contents File contents
     * @param string $extension File extension
     * @return string Full path to the stored file
     */
    public function storeContents(string $contents, string $extension = 'pdf'): string
    {
        $filename = Str::random(40) . '.' . $extension;
        $path = $this->basePath . '/' . $filename;
        
        file_put_contents($path, $contents);
        
        return $path;
    }
    
    /**
     * Get the full path for a file
     * 
     * @param string $filename
     * @return string
     */
    public function path(string $filename): string
    {
        return $this->basePath . '/' . $filename;
    }
    
    /**
     * Get the public URL for a file
     * 
     * @param string $filename
     * @return string
     */
    public function url(string $filename): string
    {
        // Determine if the file is in a public directory
        $relativePath = str_replace(public_path(), '', $this->basePath);
        $publicUrl = url($relativePath . '/' . $filename);
        
        return $publicUrl;
    }
    
    /**
     * Delete a file
     * 
     * @param string $filename
     * @return bool
     */
    public function delete(string $filename): bool
    {
        $path = $this->path($filename);
        
        if (file_exists($path)) {
            return unlink($path);
        }
        
        return false;
    }
    
    /**
     * Clean up old temporary files
     * 
     * @return int Number of files deleted
     */
    public function cleanup(): int
    {
        $count = 0;
        $threshold = time() - ($this->lifetime * 60);
        
        foreach (glob($this->basePath . '/*') as $file) {
            if (is_file($file) && filemtime($file) < $threshold) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }

    /**
     * Store an uploaded file in the temporary storage
     *
     * @param UploadedFile $file
     * @param int $lifetime Optional lifetime in minutes
     * @return string The path to the stored file
     */
    public function storeUploadedFile(UploadedFile $file, int $lifetime = null): string
    {
        Log::debug('Storing uploaded file', [
            'originalName' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mimeType' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'error' => $file->getError(),
            'isValid' => $file->isValid(),
            'path' => $file->getPath(),
            'realPath' => $file->getRealPath(),
            'exists' => file_exists($file->getRealPath()),
        ]);
        
        // Create the temp dir if it doesn't exist
        $this->ensureTempDirExists();
        
        // Generate a unique temporary filename
        $tempFileName = $this->generateTempFileName($file);
        $targetPath = $this->getTempDir() . DIRECTORY_SEPARATOR . $tempFileName;
        
        try {
            // Store the uploaded file directly using PHP's move_uploaded_file for better reliability
            if (is_uploaded_file($file->getRealPath())) {
                if (!move_uploaded_file($file->getRealPath(), $targetPath)) {
                    // If move_uploaded_file fails, try to copy the file
                    Log::warning('move_uploaded_file failed, attempting to copy file', [
                        'from' => $file->getRealPath(),
                        'to' => $targetPath,
                        'errorCode' => error_get_last() ? error_get_last()['type'] : null,
                        'errorMessage' => error_get_last() ? error_get_last()['message'] : null,
                    ]);
                    
                    copy($file->getRealPath(), $targetPath);
                }
            } else {
                // If not an uploaded file, just copy it
                Log::info('Not a PHP uploaded file, using regular copy', [
                    'realPath' => $file->getRealPath()
                ]);
                copy($file->getRealPath(), $targetPath);
            }
        } catch (\Exception $e) {
            Log::error('Exception storing uploaded file', [
                'exception' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'realPath' => $file->getRealPath(),
                'targetPath' => $targetPath,
                'exists' => file_exists($file->getRealPath())
            ]);
            throw $e;
        }
        
        // Verify the file was stored successfully
        if (!file_exists($targetPath)) {
            Log::error('File storage failed - target file does not exist', [
                'targetPath' => $targetPath,
                'originalFile' => $file->getClientOriginalName()
            ]);
            throw new \RuntimeException("Failed to store uploaded file: {$file->getClientOriginalName()}");
        }
        
        Log::debug('File stored successfully', [
            'targetPath' => $targetPath,
            'size' => filesize($targetPath),
            'exists' => file_exists($targetPath)
        ]);
        
        // Set expiration time if lifetime is provided
        if ($lifetime !== null) {
            $this->setFileExpiration($tempFileName, $lifetime);
        }
        
        return $targetPath;
    }

    /**
     * Get a temporary file path with the given extension
     * 
     * @param string $extension File extension
     * @return string Full path to the temporary file
     */
    public function getTemporaryFilePath(string $extension = 'pdf'): string
    {
        $extension = ltrim($extension, '.');
        $filename = Str::random(40) . '.' . $extension;
        $path = $this->path($filename);
        
        // Ensure the directory exists with proper permissions
        $dir = dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            // Try setting permissions explicitly for web server access
            @chmod($dir, 0777);
        }
        
        return $path;
    }

    /**
     * Get the URL for a file path
     * 
     * @param string $path Full file path
     * @return string URL for the file
     */
    public function getUrl(string $path): string
    {
        $filename = basename($path);
        
        // Check if file exists in temp directory
        if (strpos($path, $this->basePath) === 0) {
            // Use the configured URL in disk settings
            return url('temp/' . $filename);
        }
        
        return $this->url($filename);
    }

    /**
     * Store a PDF file in the public directory
     * 
     * @param string $sourcePath Source file path
     * @param string $filename Custom filename (optional)
     * @return string Relative path to the stored PDF file
     */
    public function storePdfInPublic(string $sourcePath, string $filename = null): string
    {
        // Generate a filename if not provided
        if (empty($filename)) {
            $filename = Str::random(40) . '.pdf';
        }
        
        // Ensure filename has .pdf extension
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }
        
        // Set up public PDF directory path
        $publicPdfDir = storage_path('app/public/pdf');
        
        // Ensure directory exists with proper permissions
        if (!file_exists($publicPdfDir)) {
            mkdir($publicPdfDir, 0777, true);
            // Try setting permissions explicitly for web server access
            @chmod($publicPdfDir, 0777);
        }
        
        $destPath = $publicPdfDir . '/' . $filename;
        
        // Copy the file to the public directory
        if (file_exists($sourcePath)) {
            copy($sourcePath, $destPath);
            // Set permissive read permissions
            @chmod($destPath, 0666);
        }
        
        return 'public/pdf/' . $filename;
    }
} 