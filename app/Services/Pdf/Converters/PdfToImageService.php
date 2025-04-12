<?php

namespace App\Services\Pdf\Converters;

use App\Services\Pdf\PdfInterface;
use ZipArchive;

class PdfToImageService implements PdfInterface
{
    /**
     * Operation information
     *
     * @var array
     */
    protected $info = [
        'success' => false,
        'message' => '',
        'details' => []
    ];

    /**
     * Process a PDF file and convert it to images
     *
     * @param string $inputPath Path to input PDF file
     * @param string $outputPath Path to output ZIP or image file
     * @param array $options Processing options
     * @return bool Success or failure
     */
    public function process(string $inputPath, string $outputPath, array $options = []): bool
    {
        // Increase time limit for this operation
        set_time_limit(300); // 5 minutes
        
        // Default options
        $format = strtolower($options['format'] ?? 'jpg');
        $quality = $this->getQualityValue($options['quality'] ?? 'medium');
        $dpi = $options['dpi'] ?? 150;
        $pages = $options['pages'] ?? 'all';
        $createZip = $options['create_zip'] ?? true;
        
        // Log processing start
        \Log::debug('PdfToImageService: Starting conversion', [
            'file' => $inputPath,
            'options' => $options,
            'format' => $format // Log the format explicitly
        ]);

        // Validate and check if Imagick is available
        if (!extension_loaded('imagick')) {
            $this->info['message'] = 'Imagick extension is not available';
            return false;
        }

        try {
            // Create temp directory for images
            $tempDir = storage_path('app/temp/pdf_images_' . uniqid());
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            \Log::debug('PdfToImageService: Created temp directory', ['dir' => $tempDir]);

            // Determine the image format and extension
            $imageFormat = strtoupper($format);
            $imageExtension = strtolower($format);
            
            \Log::debug('PdfToImageService: Using format', [
                'format' => $format,
                'imageFormat' => $imageFormat,
                'imageExtension' => $imageExtension
            ]);
            
            // Get the total number of pages first
            $countImagick = new \Imagick();
            $countImagick->pingImage($inputPath);
            $totalPages = $countImagick->getNumberImages();
            $countImagick->clear();
            $countImagick->destroy();
            
            \Log::debug('PdfToImageService: PDF has ' . $totalPages . ' pages');
            
            // Determine which pages to process
            $pagesToProcess = $this->getPagesToProcess($pages, $totalPages);
            
            if (empty($pagesToProcess)) {
                $this->info['message'] = 'No valid pages specified';
                return false;
            }
            
            $outputFiles = [];
            $batchSize = 5; // Process 5 pages at a time
            $pageGroups = array_chunk($pagesToProcess, $batchSize);
            
            \Log::debug('PdfToImageService: Processing ' . count($pagesToProcess) . ' pages in ' . count($pageGroups) . ' batches');
            
            // Process pages in batches
            foreach ($pageGroups as $batchIndex => $pageGroup) {
                \Log::debug('PdfToImageService: Processing batch ' . ($batchIndex + 1) . ' of ' . count($pageGroups));
                
                // Process each page in this batch
                foreach ($pageGroup as $pageNum) {
                    // Adjust for zero-based indexing in Imagick
                    $pageIndex = $pageNum - 1;
                    
                    // Create separate Imagick object for each page
                    $pageImage = new \Imagick();
                    $pageImage->setResolution($dpi, $dpi);
                    
                    // Use a specific page to reduce memory usage
                    $pageImage->readImage($inputPath . '[' . $pageIndex . ']');
                    
                    // Convert to the specified format
                    $pageImage->setImageFormat($imageFormat);
                    
                    // Set compression quality for JPG
                    if ($format === 'jpg') {
                        $pageImage->setImageCompressionQuality($quality);
                    } else if ($format === 'png') {
                        // Configure PNG quality/compression if needed
                        $pageImage->setImageCompressionQuality($quality);
                        $pageImage->setOption('png:compression-level', 9);
                    }
                    
                    // Save the image
                    $outputFilePath = $tempDir . '/page_' . str_pad($pageNum, 3, '0', STR_PAD_LEFT) . '.' . $imageExtension;
                    \Log::debug('PdfToImageService: Saving to file', [
                        'path' => $outputFilePath,
                        'format' => $format,
                        'extension' => $imageExtension
                    ]);
                    $pageImage->writeImage($outputFilePath);
                    $outputFiles[] = $outputFilePath;
                    
                    // Free memory
                    $pageImage->clear();
                    $pageImage->destroy();
                    
                    // Force garbage collection
                    gc_collect_cycles();
                    
                    \Log::debug('PdfToImageService: Processed page ' . $pageNum);
                }
                
                // Give the system some time to recover between batches
                if ($batchIndex < count($pageGroups) - 1) {
                    usleep(500000); // 0.5 second pause between batches
                }
            }
            
            // If no output files were generated, return failure
            if (empty($outputFiles)) {
                $this->info['message'] = 'Failed to convert PDF to images';
                return false;
            }
            
            \Log::debug('PdfToImageService: Generated ' . count($outputFiles) . ' image files');
            
            // If only one page was processed and we don't need a zip, just copy the file
            if (count($outputFiles) === 1 && !$createZip) {
                if (copy($outputFiles[0], $outputPath)) {
                    $this->info['success'] = true;
                    $this->info['message'] = 'PDF converted to image successfully';
                    $this->info['details'] = [
                        'pages' => count($outputFiles),
                        'format' => $format,
                        'url' => $this->getPublicUrl($outputPath),
                        'filename' => basename($outputPath)
                    ];
                    
                    // Clean up
                    $this->cleanupTempFiles($outputFiles, $tempDir);
                    return true;
                } else {
                    $this->info['message'] = 'Failed to copy the converted image';
                    return false;
                }
            }
            
            // Create a ZIP file with all images
            \Log::debug('PdfToImageService: Creating ZIP file', ['output' => $outputPath]);
            
            $zip = new ZipArchive();
            if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($outputFiles as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
                
                // Set operation info
                $this->info['success'] = true;
                $this->info['message'] = 'PDF converted to images successfully';
                $this->info['details'] = [
                    'pages' => count($outputFiles),
                    'format' => $format,
                    'url' => $this->getPublicUrl($outputPath),
                    'filename' => basename($outputPath)
                ];
                
                // Clean up
                $this->cleanupTempFiles($outputFiles, $tempDir);
                
                \Log::debug('PdfToImageService: Conversion completed successfully');
                return true;
            } else {
                $this->info['message'] = 'Failed to create ZIP file';
                \Log::error('PdfToImageService: Failed to create ZIP file', ['output' => $outputPath]);
                return false;
            }
        } catch (\Exception $e) {
            $this->info['message'] = 'Error converting PDF to images: ' . $e->getMessage();
            \Log::error('PdfToImageService: Exception during conversion', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get information about the operation
     *
     * @return array Information about current operation
     */
    public function getInfo(): array
    {
        return $this->info;
    }
    
    /**
     * Get quality value based on quality setting
     *
     * @param string $quality Quality setting (low, medium, high)
     * @return int Quality value (0-100)
     */
    protected function getQualityValue(string $quality): int
    {
        return match($quality) {
            'low' => 60,
            'high' => 95,
            default => 80, // medium
        };
    }
    
    /**
     * Parse page range specification and return array of page numbers
     *
     * @param string $pages Page range specification (e.g., "1,3-5,7" or "all")
     * @param int $totalPages Total number of pages in the document
     * @return array Array of page numbers
     */
    protected function getPagesToProcess(string $pages, int $totalPages): array
    {
        if ($pages === 'all') {
            // Limit to first 20 pages by default for performance
            $maxPages = min($totalPages, 20);
            return range(1, $maxPages);
        }
        
        $result = [];
        $parts = explode(',', $pages);
        
        foreach ($parts as $part) {
            if (strpos($part, '-') !== false) {
                // Range of pages
                list($start, $end) = explode('-', $part);
                $start = (int)$start;
                $end = (int)$end;
                
                if ($start < 1) $start = 1;
                if ($end > $totalPages) $end = $totalPages;
                
                if ($start <= $end) {
                    $result = array_merge($result, range($start, $end));
                }
            } else {
                // Single page
                $page = (int)$part;
                if ($page >= 1 && $page <= $totalPages) {
                    $result[] = $page;
                }
            }
        }
        
        // Remove duplicates and sort
        $result = array_unique($result);
        sort($result);
        
        // Limit to 50 pages maximum for performance
        if (count($result) > 50) {
            $result = array_slice($result, 0, 50);
        }
        
        return $result;
    }
    
    /**
     * Clean up temporary files and directory
     *
     * @param array $files Array of file paths to delete
     * @param string $directory Directory to remove
     * @return void
     */
    protected function cleanupTempFiles(array $files, string $directory): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        
        if (is_dir($directory)) {
            @rmdir($directory);
        }
    }
    
    /**
     * Get public URL for file
     *
     * @param string $path File path
     * @return string Public URL
     */
    protected function getPublicUrl(string $path): string
    {
        // If path is in storage/app/temp, convert to temp URL
        if (strpos($path, storage_path('app/temp')) === 0) {
            $relativePath = str_replace(storage_path('app/temp'), '', $path);
            return url('temp' . $relativePath);
        }
        
        return url('temp/' . basename($path));
    }
} 