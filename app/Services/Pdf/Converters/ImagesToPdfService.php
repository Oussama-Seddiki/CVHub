<?php

namespace App\Services\Pdf\Converters;

use App\Services\Pdf\PdfInterface;
use setasign\Fpdi\Fpdi;

class ImagesToPdfService implements PdfInterface
{
    /**
     * Operation information
     *
     * @var array
     */
    protected $info = [
        'success' => false,
        'message' => '',
        'details' => [],
        'output_path' => ''
    ];

    /**
     * Process images and convert to PDF
     *
     * @param string $inputPath Path to first input image (not used, images come from options['files'])
     * @param string $outputPath Path to output PDF file
     * @param array $options Processing options
     * @return bool Success or failure
     */
    public function process(string $inputPath, string $outputPath, array $options = []): bool
    {
        // Increase time limit for this operation
        set_time_limit(300); // 5 minutes
        
        // Default options
        $pageSize = $options['page_size'] ?? 'A4';
        $orientation = $options['orientation'] ?? 'portrait';
        $margin = $options['margin'] ?? 10;
        
        // Get the list of image files
        $files = $options['files'] ?? [];
        
        if (empty($files)) {
            $this->info['message'] = 'No image files provided';
            return false;
        }
        
        // Ensure output path has PDF extension
        $outputPath = $this->ensurePdfExtension($outputPath);
        
        \Log::debug('ImagesToPdfService: Starting conversion', [
            'files' => count($files),
            'options' => $options,
            'output_path' => $outputPath
        ]);
        
        try {
            // Try GhostScript first if available
            if ($this->isGhostscriptAvailable()) {
                $result = $this->processWithGhostscript($files, $outputPath, $pageSize, $orientation, $margin);
                if ($result) {
                    return true;
                }
            }
            
            // Fall back to Imagick if available
            if (extension_loaded('imagick') && class_exists('Imagick')) {
                $result = $this->processWithImagick($files, $outputPath, $pageSize, $orientation, $margin);
                if ($result) {
                    return true;
                }
            }
            
            // Last resort: FPDF/FPDI
            return $this->processWithFpdf($files, $outputPath, $pageSize, $orientation, $margin);
            
        } catch (\Exception $e) {
            \Log::error('ImagesToPdfService: Error during conversion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->info['success'] = false;
            $this->info['message'] = 'Failed to convert images to PDF: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Ensure path has PDF extension
     * 
     * @param string $path File path
     * @return string Path with .pdf extension
     */
    protected function ensurePdfExtension(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'pdf') {
            // Remove existing extension and add .pdf
            $path = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.pdf';
        }
        return $path;
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
     * Check if GhostScript is available
     * 
     * @return bool True if GhostScript is available
     */
    protected function isGhostscriptAvailable(): bool
    {
        $gsPath = $this->findGhostscriptExecutable();
        return $gsPath !== null;
    }
    
    /**
     * Find GhostScript executable
     * 
     * @return string|null Path to GhostScript or null if not found
     */
    protected function findGhostscriptExecutable(): ?string 
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        // Check for ghostscript binary paths
        if ($isWindows) {
            // Windows - check multiple versions
            $gsVersions = [];
            $gsBaseDirs = [
                'C:\\Program Files\\gs\\',
                'C:\\Program Files (x86)\\gs\\'
            ];
            
            foreach ($gsBaseDirs as $baseDir) {
                if (is_dir($baseDir)) {
                    $versionDirs = glob($baseDir . 'gs*', GLOB_ONLYDIR);
                    foreach ($versionDirs as $versionDir) {
                        $gsExecutable = $versionDir . '\\bin\\gswin64c.exe';
                        $gsExecutable32 = $versionDir . '\\bin\\gswin32c.exe';
                        
                        if (file_exists($gsExecutable)) {
                            $gsVersions[] = $gsExecutable;
                        } elseif (file_exists($gsExecutable32)) {
                            $gsVersions[] = $gsExecutable32;
                        }
                    }
                }
            }
            
            // Sort versions descending and use the latest
            if (!empty($gsVersions)) {
                rsort($gsVersions);
                return $gsVersions[0];
            }
            
            // Try command line which/where
            if (function_exists('exec')) {
                exec('where gswin64c 2>NUL', $output, $returnVal);
                if ($returnVal === 0 && !empty($output[0])) {
                    return $output[0];
                }
                
                exec('where gswin32c 2>NUL', $output, $returnVal);
                if ($returnVal === 0 && !empty($output[0])) {
                    return $output[0];
                }
            }
        } else {
            // Unix/Linux/Mac - use which command
            if (function_exists('exec')) {
                exec('which gs 2>/dev/null', $output, $returnVal);
                if ($returnVal === 0 && !empty($output[0])) {
                    return $output[0];
                }
            }
            
            // Check common paths
            $unixPaths = [
                '/usr/bin/gs',
                '/usr/local/bin/gs',
                '/opt/local/bin/gs'
            ];
            
            foreach ($unixPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Process images with GhostScript
     * 
     * @param array $files List of image file paths
     * @param string $outputPath Output PDF path
     * @param string $pageSize Page size (A4, Letter, etc.)
     * @param string $orientation Page orientation (portrait or landscape)
     * @param int $margin Margin in mm
     * @return bool Success or failure
     */
    protected function processWithGhostscript(array $files, string $outputPath, string $pageSize, string $orientation, int $margin): bool
    {
        \Log::debug('ImagesToPdfService: Using GhostScript method');
        
        try {
            // Get GhostScript binary path
            $gsBin = $this->findGhostscriptExecutable();
            
            if (!$gsBin) {
                \Log::warning('ImagesToPdfService: GhostScript executable not found');
                return false;
            }
            
            // Ensure temp directory exists
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Get page dimensions
            list($pageWidth, $pageHeight) = $this->getPointDimensions($pageSize, $orientation);
            
            // Create a device settings file for proper image handling
            $deviceSettingsFile = $tempDir . '/gs_settings_' . uniqid() . '.txt';
            $settings = "-dBATCH -dNOPAUSE -sDEVICE=pdfwrite -sOutputFile=\"$outputPath\" " . 
                      "-dCompatibilityLevel=1.5 -dPDFSETTINGS=/prepress " .
                      "-dEPSCrop -dAutoRotatePages=/None " .
                      "-dColorConversionStrategy=/LeaveColorUnchanged " .
                      "-dEncodeColorImages=true -dEncodeGrayImages=true " .
                      "-dDownsampleColorImages=false -dDownsampleGrayImages=false " .
                      "-dAutoFilterColorImages=false -dAutoFilterGrayImages=false " .
                      "-dColorImageFilter=/FlateEncode -dGrayImageFilter=/FlateEncode " .
                      "-dUseFlateCompression=true -dUseCIEColor=true";
            file_put_contents($deviceSettingsFile, $settings);
            
            // Create a PostScript file list for all images
            $psListFile = $tempDir . '/ps_list_' . uniqid() . '.txt';
            
            // Build the file list
            $fileList = '';
            foreach ($files as $imagePath) {
                $fileList .= "\"$imagePath\"\n";
            }
            file_put_contents($psListFile, $fileList);
            
            // Construct the command - use file list to avoid command line length issues
            $cmd = '"' . $gsBin . '" @"' . $deviceSettingsFile . '" @"' . $psListFile . '"';
            
            \Log::debug('ImagesToPdfService: Executing GhostScript', [
                'command' => $cmd,
                'file_count' => count($files)
            ]);
            
            // Execute the command
            $output = [];
            $result = 0;
            exec($cmd, $output, $result);
            
            // Clean up temporary files
            @unlink($deviceSettingsFile);
            @unlink($psListFile);
            
            if ($result === 0 && file_exists($outputPath)) {
                \Log::info('ImagesToPdfService: GhostScript conversion successful', [
                    'output_path' => $outputPath,
                    'file_size' => filesize($outputPath)
                ]);
                
                $this->info['success'] = true;
                $this->info['message'] = 'Images converted to PDF successfully using GhostScript';
                $this->info['output_path'] = $this->getRelativePath($outputPath);
                $this->info['details'] = [
                    'page_count' => count($files),
                    'page_size' => $pageSize,
                    'orientation' => $orientation,
                    'output_file_size' => filesize($outputPath),
                    'conversion_method' => 'ghostscript'
                ];
                
                return true;
            }
            
            // GhostScript failed, log error and try other methods
            \Log::error('ImagesToPdfService: GhostScript failed', [
                'result_code' => $result,
                'output' => $output
            ]);
            
            return false;
        } catch (\Exception $e) {
            \Log::error('ImagesToPdfService: GhostScript error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get page dimensions in points for GhostScript
     * 
     * @param string $pageSize Page size name (A4, Letter, etc.)
     * @param string $orientation Page orientation (portrait/landscape)
     * @return array [width, height] in points
     */
    protected function getPointDimensions(string $pageSize, string $orientation): array
    {
        // Page dimensions in points (72 points = 1 inch)
        $dimensions = [
            'A4' => [595, 842],     // 210mm × 297mm
            'A3' => [842, 1191],    // 297mm × 420mm
            'Letter' => [612, 792], // 8.5" × 11"
            'Legal' => [612, 1008]  // 8.5" × 14"
        ];
        
        // Get dimensions for the requested page size
        $pageDimensions = $dimensions[$pageSize] ?? $dimensions['A4'];
        
        // Swap dimensions for landscape orientation
        if ($orientation === 'landscape') {
            return [$pageDimensions[1], $pageDimensions[0]];
        }
        
        return $pageDimensions;
    }
    
    /**
     * Process images with Imagick (preferred method)
     *
     * @param array $files List of image file paths
     * @param string $outputPath Output PDF path
     * @param string $pageSize Page size (A4, Letter, etc.)
     * @param string $orientation Page orientation (portrait or landscape)
     * @param int $margin Margin in mm
     * @return bool Success or failure
     */
    protected function processWithImagick(array $files, string $outputPath, string $pageSize, string $orientation, int $margin): bool
    {
        \Log::debug('ImagesToPdfService: Using Imagick method');
        
        try {
            // Create a new Imagick object
            $pdf = new \Imagick();
            
            // Set PDF resolution (DPI)
            $dpi = 300;
            
            // Calculate page dimensions in pixels based on DPI
            list($pageWidth, $pageHeight) = $this->getPageDimensions($pageSize, $orientation, $dpi);
            
            // Calculate margins in pixels
            $marginPx = $margin * $dpi / 25.4; // convert mm to pixels
            
            // Process each image
            foreach ($files as $index => $imagePath) {
                \Log::debug("ImagesToPdfService: Processing image $index", ['path' => $imagePath]);
                
                // Load the image
                $image = new \Imagick($imagePath);
                
                // Set the image format to ensure proper handling
                $image->setImageFormat('jpeg');
                
                // Calculate the image dimensions while preserving aspect ratio
                $imageWidth = $image->getImageWidth();
                $imageHeight = $image->getImageHeight();
                
                // Calculate the available space on the page (accounting for margins)
                $availableWidth = $pageWidth - (2 * $marginPx);
                $availableHeight = $pageHeight - (2 * $marginPx);
                
                // Calculate the scaling factor to fit the image within the available space
                $widthRatio = $availableWidth / $imageWidth;
                $heightRatio = $availableHeight / $imageHeight;
                $scale = min($widthRatio, $heightRatio);
                
                // Calculate new dimensions
                $newWidth = $imageWidth * $scale;
                $newHeight = $imageHeight * $scale;
                
                // Resize the image
                $image->resizeImage($newWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1);
                
                // Create a new canvas with white background for the page
                $page = new \Imagick();
                $page->newImage($pageWidth, $pageHeight, new \ImagickPixel('white'), 'pdf');
                
                // Calculate position to center the image on the page
                $x = ($pageWidth - $newWidth) / 2;
                $y = ($pageHeight - $newHeight) / 2;
                
                // Composite the image onto the page
                $page->compositeImage($image, \Imagick::COMPOSITE_DEFAULT, $x, $y);
                
                // Add the page to the PDF
                $pdf->addImage($page);
                
                // Clean up
                $image->clear();
                $image->destroy();
                $page->clear();
                $page->destroy();
                
                // Force garbage collection to free memory
                gc_collect_cycles();
            }
            
            // Set format to PDF
            $pdf->setImageFormat('pdf');
            
            // Write the PDF to the output file
            if ($pdf->writeImages($outputPath, true)) {
                $this->info['success'] = true;
                $this->info['message'] = 'Images converted to PDF successfully';
                $this->info['output_path'] = $this->getRelativePath($outputPath);
                $this->info['details'] = [
                    'page_count' => count($files),
                    'page_size' => $pageSize,
                    'orientation' => $orientation
                ];
                $this->info['conversion_method'] = 'imagick';
                
                // Clean up
                $pdf->clear();
                $pdf->destroy();
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            \Log::error('ImagesToPdfService: Imagick processing error', [
                'error' => $e->getMessage()
            ]);
            
            // Try the fallback method
            return $this->processWithFpdf($files, $outputPath, $pageSize, $orientation, $margin);
        }
    }
    
    /**
     * Process images with FPDF (fallback method)
     * 
     * @param array $files List of image file paths
     * @param string $outputPath Output PDF path
     * @param string $pageSize Page size (A4, Letter, etc.)
     * @param string $orientation Page orientation (portrait or landscape)
     * @param int $margin Margin in mm
     * @return bool Success or failure
     */
    protected function processWithFpdf(array $files, string $outputPath, string $pageSize, string $orientation, int $margin): bool
    {
        \Log::debug('ImagesToPdfService: Using FPDF method');
        
        try {
            // We need to ensure FPDI is available via Composer
            if (!class_exists('setasign\\Fpdi\\Fpdi')) {
                \Log::error('ImagesToPdfService: FPDI library not available');
                $this->info['message'] = 'PDF library not available';
                return false;
            }
            
            // Create PDF instance
            $pdf = new Fpdi();
            
            // Set page orientation
            $pdf->SetAutoPageBreak(false);
            
            // Process each image
            foreach ($files as $imagePath) {
                // Get image dimensions
                list($width, $height) = getimagesize($imagePath);
                
                // Determine image orientation
                $imageOrientation = ($width > $height) ? 'L' : 'P';
                
                // Use specified orientation unless auto is selected
                $finalOrientation = ($orientation === 'auto') ? $imageOrientation : 
                                   (($orientation === 'landscape') ? 'L' : 'P');
                
                // Add page with correct size and orientation
                $pdf->AddPage($finalOrientation, $pageSize);
                
                // Calculate usable page dimensions
                $pageWidth = $pdf->GetPageWidth() - (2 * $margin);
                $pageHeight = $pdf->GetPageHeight() - (2 * $margin);
                
                // Scale image to fit within page while maintaining aspect ratio
                $widthRatio = $pageWidth / $width;
                $heightRatio = $pageHeight / $height;
                $ratio = min($widthRatio, $heightRatio);
                
                $newWidth = $width * $ratio;
                $newHeight = $height * $ratio;
                
                // Center image on page
                $x = $margin + ($pageWidth - $newWidth) / 2;
                $y = $margin + ($pageHeight - $newHeight) / 2;
                
                // Add image to page
                $pdf->Image($imagePath, $x, $y, $newWidth, $newHeight);
            }
            
            // Output PDF
            $pdf->Output('F', $outputPath);
            
            $this->info['success'] = true;
            $this->info['message'] = 'Images converted to PDF successfully';
            $this->info['output_path'] = $this->getRelativePath($outputPath);
            $this->info['details'] = [
                'page_count' => count($files),
                'page_size' => $pageSize,
                'orientation' => $orientation
            ];
            $this->info['conversion_method'] = 'fpdf';
            
            return true;
        } catch (\Exception $e) {
            \Log::error('ImagesToPdfService: FPDF processing error', [
                'error' => $e->getMessage()
            ]);
            
            $this->info['success'] = false;
            $this->info['message'] = 'Failed to convert images to PDF: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Get page dimensions in pixels based on page size, orientation and DPI
     * 
     * @param string $pageSize Page size name (A4, Letter, etc.)
     * @param string $orientation Page orientation (portrait/landscape)
     * @param int $dpi Resolution in dots per inch
     * @return array [width, height] in pixels
     */
    protected function getPageDimensions(string $pageSize, string $orientation, int $dpi): array
    {
        // Page dimensions in mm
        $dimensions = [
            'A4' => [210, 297],
            'A3' => [297, 420],
            'Letter' => [216, 279],
            'Legal' => [216, 356]
        ];
        
        // Get dimensions for the requested page size
        $pageDimensions = $dimensions[$pageSize] ?? $dimensions['A4'];
        
        // Convert mm to inches and then to pixels
        $widthInches = $pageDimensions[0] / 25.4;
        $heightInches = $pageDimensions[1] / 25.4;
        
        $widthPx = round($widthInches * $dpi);
        $heightPx = round($heightInches * $dpi);
        
        // Swap dimensions for landscape orientation
        if ($orientation === 'landscape') {
            return [$heightPx, $widthPx];
        }
        
        return [$widthPx, $heightPx];
    }
    
    /**
     * Get relative path from storage path
     * 
     * @param string $fullPath Full file path
     * @return string Relative path
     */
    protected function getRelativePath(string $fullPath): string
    {
        // Get storage path
        $storagePath = storage_path('app');
        
        // Ensure path has PDF extension
        $fullPath = $this->ensurePdfExtension($fullPath);
        
        // Use the temporary storage service to store the file in public/pdf
        if (file_exists($fullPath)) {
            try {
                // Get TemporaryStorage service
                $tempStorage = app(\App\Services\Storage\TemporaryStorage::class);
                
                // Store the file in public/pdf directory
                $relativePath = $tempStorage->storePdfInPublic($fullPath);
                
                \Log::debug('ImagesToPdfService: Stored file in public PDF directory', [
                    'original_path' => $fullPath,
                    'public_path' => $relativePath
                ]);
                
                return $relativePath;
            } catch (\Exception $e) {
                \Log::error('ImagesToPdfService: Error storing file in public directory', [
                    'error' => $e->getMessage(),
                    'original_path' => $fullPath
                ]);
            }
        }
        
        // Fallback to the original path
        // Create a specific path for PDF files
        $relativePath = 'public/pdf/' . basename($fullPath);
        $publicStoragePath = storage_path('app/' . $relativePath);
        
        // Ensure directory exists
        if (!is_dir(dirname($publicStoragePath))) {
            mkdir(dirname($publicStoragePath), 0755, true);
        }
        
        // Copy file to public storage if it's not already there
        if (file_exists($fullPath) && $fullPath !== $publicStoragePath) {
            copy($fullPath, $publicStoragePath);
            // Set permissive read permissions
            @chmod($publicStoragePath, 0666);
            
            \Log::debug('ImagesToPdfService: Copied file to public storage (fallback)', [
                'from' => $fullPath,
                'to' => $publicStoragePath
            ]);
        }
        
        return $relativePath;
    }
} 