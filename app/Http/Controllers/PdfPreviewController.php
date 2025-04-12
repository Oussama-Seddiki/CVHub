<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\PdfToImage\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class PdfPreviewController extends Controller
{
    /**
     * Generate preview images for a PDF file
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generatePreview(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:20000',
        ]);
        
        try {
            // Get the uploaded file
            $file = $request->file('pdf');
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.pdf';
            
            // Store the PDF for processing
            $path = $file->storeAs('temp', $fileName);
            $fullPath = Storage::path($path);
            
            // Log the file details
            \Log::debug('PDF Preview Generation', [
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $fullPath,
                'size' => $file->getSize()
            ]);
            
            // Create a unique preview directory
            $previewDir = 'pdf_previews/' . time() . '_' . Str::random(8);
            Storage::makeDirectory('public/' . $previewDir);
            
            // Try to get page count using fallback methods
            $pageCount = $this->getPageCount($fullPath);
            
            // Log the page count
            \Log::debug('PDF page count detected', [
                'pages' => $pageCount
            ]);
            
            // Store the original PDF for later download with public access
            $storedPdfPath = $previewDir . '/original.pdf';
            Storage::disk('public')->put($storedPdfPath, file_get_contents($fullPath));
            
            // Generate actual preview thumbnails
            $previews = [];
            $maxPreviewPages = min($pageCount, 100); // Increased from 10 to 100 pages for better display
            $previewsGenerated = 0;
            
            // Try Imagick first if available
            if (extension_loaded('imagick') && class_exists('Imagick')) {
                try {
                    \Log::debug('Attempting to generate thumbnails using Imagick');
                    
                    for ($i = 0; $i < $maxPreviewPages; $i++) {
                        $pageNum = $i + 1;
                        $outputPath = $previewDir . "/page-$pageNum.jpg";
                        $fullOutputPath = Storage::disk('public')->path($outputPath);
                        
                        // Create directory if doesn't exist
                        if (!is_dir(dirname($fullOutputPath))) {
                            mkdir(dirname($fullOutputPath), 0755, true);
                        }
                        
                        // Create Imagick object with specific page
                        $im = new \Imagick();
                        $im->setResolution(72, 72); // Lower resolution for thumbnails
                        
                        try {
                            // Try with timeout to prevent hanging on corrupt pages
                            $im->readImage($fullPath . "[" . $i . "]"); // Zero-based page index
                            $im->setImageFormat('jpeg');
                            $im->setImageCompression(\Imagick::COMPRESSION_JPEG);
                            $im->setImageCompressionQuality(80); // Increase quality
                            
                            // Fix orientation and ensure white background
                            $im->setImageBackgroundColor('white');
                            $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                            $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                            
                            // Create a proper thumbnail with width 400px for better display
                            $im->thumbnailImage(400, 0); // Width = 400px, height proportional
                            
                            if ($im->writeImage($fullOutputPath)) {
                                $previews[] = [
                                    'pageNumber' => $pageNum,
                                    'dataUrl' => '/storage/' . $outputPath . '?v=' . time() // Add cache buster
                                ];
                                $previewsGenerated++;
                            }
                        } catch (\Exception $pageException) {
                            \Log::warning("Error processing page $pageNum with Imagick", [
                                'error' => $pageException->getMessage()
                            ]);
                            
                            // Add placeholder for this page
                            $previews[] = [
                                'pageNumber' => $pageNum,
                                'dataUrl' => null // Frontend will use placeholder
                            ];
                        }
                        
                        // Clean up
                        $im->clear();
                        $im->destroy();
                    }
                    
                    \Log::debug('Successfully generated previews using Imagick', [
                        'count' => $previewsGenerated
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Imagick preview generation failed, falling back to alternative methods', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Try pdftoppm (from poppler-utils) if Imagick failed or generated less than half the pages
            if ($previewsGenerated < ($maxPreviewPages / 2) && $this->commandExists('pdftoppm')) {
                try {
                    \Log::debug('Attempting to generate thumbnails using pdftoppm');
                    
                    // Clear previews if Imagick was very unsuccessful
                    if ($previewsGenerated < 3) {
                        $previews = [];
                        $previewsGenerated = 0;
                    }
                    
                    for ($i = 1; $i <= $maxPreviewPages; $i++) {
                        // Skip if we already have this page from Imagick
                        $existingIndex = array_search($i, array_column($previews, 'pageNumber'));
                        if ($existingIndex !== false && $previews[$existingIndex]['dataUrl'] !== null) {
                            continue;
                        }
                        
                        $outputPath = $previewDir . "/page-$i.jpg";
                        $fullOutputPath = Storage::disk('public')->path($outputPath);
                        
                        // Create directory if doesn't exist
                        if (!is_dir(dirname($fullOutputPath))) {
                            mkdir(dirname($fullOutputPath), 0755, true);
                        }
                        
                        // Use pdftoppm to convert PDF page to image with timeout for safety
                        $command = "pdftoppm -jpeg -jpegopt quality=85 -f $i -l $i -scale-to 400 \"$fullPath\" \"" . dirname($fullOutputPath) . "/" . basename($outputPath, '.jpg') . "\"";
                        
                        // Execute with a timeout (30 seconds per page)
                        $descriptorspec = [
                            0 => ["pipe", "r"],  // stdin
                            1 => ["pipe", "w"],  // stdout
                            2 => ["pipe", "w"]   // stderr
                        ];
                        
                        $process = proc_open($command, $descriptorspec, $pipes);
                        if (is_resource($process)) {
                            // Set a timeout
                            $status = proc_get_status($process);
                            $start = time();
                            
                            // Check process for max 30 seconds
                            while (time() - $start < 30 && proc_get_status($process)['running']) {
                                usleep(100000); // Sleep for 0.1 seconds
                            }
                            
                            // Kill process if still running
                            if (proc_get_status($process)['running']) {
                                proc_terminate($process);
                                \Log::warning("pdftoppm timed out for page $i");
                                proc_close($process);
                                continue;
                            }
                            
                            proc_close($process);
                        }
                        
                        // pdftoppm adds -1.jpg to the filename, so we need to find and rename
                        $generatedFile = dirname($fullOutputPath) . "/" . basename($outputPath, '.jpg') . "-1.jpg";
                        if (file_exists($generatedFile)) {
                            rename($generatedFile, $fullOutputPath);
                            
                            // If this is a new page, add it
                            if ($existingIndex === false) {
                                $previews[] = [
                                    'pageNumber' => $i,
                                    'dataUrl' => '/storage/' . $outputPath . '?v=' . time() // Add cache buster
                                ];
                            } else {
                                // Update existing placeholder
                                $previews[$existingIndex]['dataUrl'] = '/storage/' . $outputPath . '?v=' . time(); // Add cache buster
                            }
                            $previewsGenerated++;
                        } else {
                            // Add placeholder if page couldn't be generated and doesn't exist
                            if ($existingIndex === false) {
                                $previews[] = [
                                    'pageNumber' => $i,
                                    'dataUrl' => null
                                ];
                            }
                        }
                    }
                    
                    \Log::debug('Successfully generated previews using pdftoppm', [
                        'count' => $previewsGenerated
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('pdftoppm preview generation failed, falling back to alternative methods', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Ensure we have entries for all pages (sorted by page number)
            $pageEntries = [];
            for ($i = 1; $i <= $pageCount; $i++) {
                $existingIndex = array_search($i, array_column($previews, 'pageNumber'));
                if ($existingIndex !== false) {
                    $pageEntries[] = $previews[$existingIndex];
                } else {
                    $pageEntries[] = [
                        'pageNumber' => $i,
                        'dataUrl' => null // Frontend will use placeholders
                    ];
                }
            }
            
            // Clean up temporary file
            Storage::delete($path);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF processed successfully',
                'pageCount' => $pageCount,
                'previews' => $pageEntries,
                'fileUrl' => '/storage/' . $storedPdfPath, // Use direct public path instead of Storage::url
                'previewsGenerated' => $previewsGenerated > 0 // Indicates if we have actual previews
            ]);
            
        } catch (\Exception $e) {
            // Log the error
            \Log::error('PDF Preview Generation Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process PDF: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process a PDF file to remove specific pages
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function removePages(Request $request)
    {
        $request->validate([
            'pdf_path' => 'required|string',
            'pages_to_remove' => 'required|string',
        ]);
        
        try {
            // Get the original PDF file path
            $pdfPath = $request->input('pdf_path');
            
            // Log the incoming path for debugging
            \Log::debug('PDF removal request received', [
                'pdf_path' => $pdfPath,
                'pages_to_remove' => $request->input('pages_to_remove')
            ]);
            
            // Handle different path formats that could be sent from frontend
            if (Str::startsWith($pdfPath, '/storage/')) {
                // If path starts with /storage, convert it to a relative path for Storage facade
                $pdfPath = Str::replaceFirst('/storage/', '', $pdfPath);
            } elseif (Str::startsWith($pdfPath, 'storage/')) {
                // Handle case without leading slash
                $pdfPath = Str::replaceFirst('storage/', '', $pdfPath);
            } elseif (Str::startsWith($pdfPath, 'http://') || Str::startsWith($pdfPath, 'https://')) {
                // Handle full URLs by extracting the path
                $parsedUrl = parse_url($pdfPath);
                if (isset($parsedUrl['path']) && Str::contains($parsedUrl['path'], '/storage/')) {
                    $pdfPath = Str::after($parsedUrl['path'], '/storage/');
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid file path format'
                    ], 400);
                }
            }
            
            // Ensure we have a valid path now
            $fullPath = Storage::disk('public')->path($pdfPath);
            
            // Log the resolved path
            \Log::debug('PDF path resolved', [
                'resolved_path' => $pdfPath,
                'full_path' => $fullPath,
                'exists' => file_exists($fullPath)
            ]);
            
            // Check if file exists
            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Original PDF file not found'
                ], 404);
            }
            
            // Convert pages to remove from string to array of integers
            $pagesToRemove = array_map('intval', explode(',', $request->input('pages_to_remove')));
            
            // Create a new output directory
            $outputDir = 'pdf_output/' . time() . '_' . Str::random(8);
            Storage::makeDirectory('public/' . $outputDir);
            $outputPath = $outputDir . '/result.pdf';
            $outputFullPath = Storage::disk('public')->path($outputPath);
            
            // Get total page count
            $pageCount = $this->getPageCount($fullPath);
            
            // Build the list of pages to keep
            $pagesToKeep = array_diff(range(1, $pageCount), $pagesToRemove);
            
            if (empty($pagesToKeep)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove all pages from the document'
                ], 400);
            }
            
            // Try different PDF tools first if available
            $success = false;
            
            // Try pdftk first (most common)
            if ($this->commandExists('pdftk')) {
                $pageRanges = implode(' ', $pagesToKeep);
                $command = "pdftk \"$fullPath\" cat $pageRanges output \"$outputFullPath\"";
                exec($command, $output, $returnCode);
                $success = ($returnCode === 0 && file_exists($outputFullPath));
            }
            
            // If pdftk fails, try qpdf
            if (!$success && $this->commandExists('qpdf')) {
                $pageRanges = $this->buildPageRangeString($pagesToKeep);
                $command = "qpdf --empty --pages \"$fullPath\" $pageRanges -- \"$outputFullPath\"";
                exec($command, $output, $returnCode);
                $success = ($returnCode === 0 && file_exists($outputFullPath));
            }
            
            // If external tools failed, use PHP-based solution
            if (!$success) {
                $success = $this->removePagesPHP($fullPath, $outputFullPath, $pagesToKeep);
            }
            
            if (!$success) {
                throw new \Exception('Failed to process PDF file. All PDF processing methods failed.');
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Pages removed successfully',
                'file' => '/storage/' . $outputPath,
                'filename' => 'modified-document-' . date('YmdHis') . '.pdf',
                'removedPages' => $pagesToRemove
            ]);
            
        } catch (\Exception $e) {
            \Log::error('PDF Page Removal Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove pages: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove pages from a PDF file using PHP (FPDI/FPDF)
     *
     * @param string $inputPath
     * @param string $outputPath
     * @param array $pagesToKeep
     * @return bool
     */
    private function removePagesPHP($inputPath, $outputPath, $pagesToKeep)
    {
        try {
            // Validate input file exists
            if (!file_exists($inputPath)) {
                \Log::error('PDF input file does not exist', ['path' => $inputPath]);
                return false;
            }
            
            // Ensure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                if (!mkdir($outputDir, 0755, true)) {
                    \Log::error('Failed to create output directory', ['dir' => $outputDir]);
                    return false;
                }
            }
            
            // Create new PDF document
            $pdf = new Fpdi();
            
            // Set source file and handle potential errors
            try {
                $pageCount = $pdf->setSourceFile($inputPath);
                \Log::debug('Source PDF loaded successfully', [
                    'path' => $inputPath,
                    'page_count' => $pageCount
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to load PDF source file', [
                    'path' => $inputPath, 
                    'error' => $e->getMessage()
                ]);
                return false;
            }
            
            // Check if we have valid pages to keep
            if (empty($pagesToKeep)) {
                \Log::error('No pages to keep specified');
                return false;
            }
            
            // Log pages we're keeping
            \Log::debug('Processing PDF to keep specific pages', [
                'pages_to_keep' => $pagesToKeep,
                'total_pages' => $pageCount
            ]);
            
            // Sort pages to keep
            sort($pagesToKeep);
            
            // Track if we successfully added any pages
            $addedPages = 0;
            
            // Loop through pages to keep and add them to new PDF
            foreach ($pagesToKeep as $pageNumber) {
                // Skip if page doesn't exist
                if ($pageNumber > $pageCount || $pageNumber < 1) {
                    \Log::warning('Skipping invalid page number', ['page' => $pageNumber]);
                    continue;
                }
                
                try {
                    // Import page
                    $template = $pdf->importPage($pageNumber);
                    $size = $pdf->getTemplateSize($template);
                    
                    // Add page with same orientation and size
                    $pdf->AddPage(
                        $size['width'] > $size['height'] ? 'L' : 'P', 
                        [$size['width'], $size['height']]
                    );
                    
                    // Use the imported page
                    $pdf->useTemplate($template);
                    $addedPages++;
                } catch (\Exception $e) {
                    \Log::warning('Failed to import page', [
                        'page' => $pageNumber,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with other pages
                    continue;
                }
            }
            
            // Check if we successfully added any pages
            if ($addedPages === 0) {
                \Log::error('Failed to add any pages to the new PDF');
                return false;
            }
            
            try {
                // Output the new PDF
                $pdf->Output($outputPath, 'F');
                
                // Verify the file was created and has content
                if (file_exists($outputPath) && filesize($outputPath) > 0) {
                    \Log::debug('PDF created successfully', [
                        'path' => $outputPath,
                        'size' => filesize($outputPath),
                        'pages_added' => $addedPages
                    ]);
                    return true;
                } else {
                    \Log::error('PDF creation failed or resulted in empty file', [
                        'path' => $outputPath,
                        'exists' => file_exists($outputPath),
                        'size' => file_exists($outputPath) ? filesize($outputPath) : 0
                    ]);
                    return false;
                }
            } catch (\Exception $e) {
                \Log::error('Error saving PDF file', [
                    'path' => $outputPath,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('PHP PDF Processing Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Get the page count of a PDF file using various fallback methods
     *
     * @param string $pdfPath
     * @return int
     */
    private function getPageCount($pdfPath)
    {
        try {
            // Try pdftk if available (most reliable)
            if ($this->commandExists('pdftk')) {
                $command = "pdftk \"$pdfPath\" dump_data | grep NumberOfPages";
                exec($command, $output, $returnCode);
                
                if ($returnCode === 0 && !empty($output)) {
                    foreach ($output as $line) {
                        if (preg_match('/NumberOfPages: (\d+)/', $line, $matches)) {
                            return (int)$matches[1];
                        }
                    }
                }
            }
            
            // Try pdfinfo if available
            if ($this->commandExists('pdfinfo')) {
                $command = "pdfinfo \"$pdfPath\" | grep Pages";
                exec($command, $output, $returnCode);
                
                if ($returnCode === 0 && !empty($output)) {
                    foreach ($output as $line) {
                        if (preg_match('/Pages:\s+(\d+)/', $line, $matches)) {
                            return (int)$matches[1];
                        }
                    }
                }
            }
            
            // If external tools fail, try to parse the PDF directly
            $pageCount = $this->parsePdfPageCount($pdfPath);
            if ($pageCount > 0) {
                return $pageCount;
            }
            
            // If all else fails, default to a reasonable number
            return 1;
            
        } catch (\Exception $e) {
            \Log::error('Error getting PDF page count: ' . $e->getMessage());
            return 1; // Default to 1 page on error
        }
    }
    
    /**
     * Build a page range string for QPDF
     *
     * @param array $pages
     * @return string
     */
    private function buildPageRangeString($pages)
    {
        sort($pages);
        $ranges = [];
        $start = $end = $pages[0];
        
        for ($i = 1; $i < count($pages); $i++) {
            if ($pages[$i] == $end + 1) {
                $end = $pages[$i];
            } else {
                $ranges[] = ($start == $end) ? $start : "$start-$end";
                $start = $end = $pages[$i];
            }
        }
        
        $ranges[] = ($start == $end) ? $start : "$start-$end";
        
        return implode(',', $ranges);
    }
    
    /**
     * Check if a command exists on the system
     *
     * @param string $command
     * @return bool
     */
    private function commandExists($command)
    {
        $whereIsCommand = PHP_OS === 'WINNT' ? "where $command" : "which $command";
        $returnVal = 0;
        
        exec($whereIsCommand, $output, $returnVal);
        return $returnVal === 0;
    }
    
    /**
     * Parse PDF to count pages without external tools
     * 
     * @param string $pdfPath
     * @return int
     */
    private function parsePdfPageCount($pdfPath)
    {
        $content = file_get_contents($pdfPath);
        
        // Check if it's a valid PDF
        if (substr($content, 0, 4) !== '%PDF') {
            return 0;
        }
        
        // Look for "Pages" object with Count attribute
        if (preg_match('/\/Type\s*\/Pages.*?\/Count\s+(\d+)/s', $content, $matches)) {
            return (int)$matches[1];
        }
        
        // Alternative pattern
        if (preg_match('/\/Count\s+(\d+).*?\/Type\s*\/Pages/s', $content, $matches)) {
            return (int)$matches[1];
        }
        
        // Count page objects as a last resort
        $pageCount = substr_count($content, '/Type /Page');
        if ($pageCount > 0) {
            return $pageCount;
        }
        
        return 0;
    }
    
    /**
     * Process a PDF file to extract specific pages
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function extractPages(Request $request)
    {
        $request->validate([
            'pdf_path' => 'string|required_without:file',
            'file' => 'file|mimes:pdf|required_without:pdf_path',
            'pages_to_extract' => 'string|required_without:pages',
            'pages' => 'string|required_without:pages_to_extract',
        ]);
        
        try {
            // Handle different input methods (file upload or path reference)
            if ($request->hasFile('file')) {
                // Handle file upload
                $file = $request->file('file');
                $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.pdf';
                
                // Store the PDF for processing
                $path = $file->storeAs('temp', $fileName);
                $fullPath = Storage::path($path);
                
                // Log the file upload
                \Log::debug('PDF extraction request with file upload', [
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_path' => $fullPath
                ]);
            } else {
                // Handle path reference
                $pdfPath = $request->input('pdf_path');
                
                // Log the incoming path for debugging
                \Log::debug('PDF extraction request with path reference', [
                    'pdf_path' => $pdfPath
                ]);
                
                // Handle different path formats that could be sent from frontend
                if (Str::startsWith($pdfPath, '/storage/')) {
                    // If path starts with /storage, convert it to a relative path for Storage facade
                    $pdfPath = Str::replaceFirst('/storage/', '', $pdfPath);
                } elseif (Str::startsWith($pdfPath, 'storage/')) {
                    // Handle case without leading slash
                    $pdfPath = Str::replaceFirst('storage/', '', $pdfPath);
                } elseif (Str::startsWith($pdfPath, 'http://') || Str::startsWith($pdfPath, 'https://')) {
                    // Handle full URLs by extracting the path
                    $parsedUrl = parse_url($pdfPath);
                    if (isset($parsedUrl['path']) && Str::contains($parsedUrl['path'], '/storage/')) {
                        $pdfPath = Str::after($parsedUrl['path'], '/storage/');
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid file path format'
                        ], 400);
                    }
                }
                
                // Ensure we have a valid path now
                $fullPath = Storage::disk('public')->path($pdfPath);
                
                // Log the resolved path
                \Log::debug('PDF path resolved for extraction', [
                    'resolved_path' => $pdfPath,
                    'full_path' => $fullPath,
                    'exists' => file_exists($fullPath)
                ]);
            }
            
            // Check if file exists
            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Original PDF file not found'
                ], 404);
            }
            
            // Get the pages to extract (from either parameter)
            $pages = $request->input('pages_to_extract', $request->input('pages'));
            
            // Convert pages from string to array of integers
            $pagesToExtract = array_map('intval', explode(',', $pages));
            
            // Create a new output directory
            $outputDir = 'pdf_output/' . time() . '_' . Str::random(8);
            Storage::makeDirectory('public/' . $outputDir);
            $outputPath = $outputDir . '/result.pdf';
            $outputFullPath = Storage::disk('public')->path($outputPath);
            
            // Get total page count
            $pageCount = $this->getPageCount($fullPath);
            
            // Validate pages to extract
            $validPageNumbers = array_filter($pagesToExtract, function($page) use ($pageCount) {
                return $page > 0 && $page <= $pageCount;
            });
            
            if (empty($validPageNumbers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid pages specified for extraction'
                ], 400);
            }
            
            // Try different PDF tools first if available
            $success = false;
            
            // Try pdftk first (most common)
            if ($this->commandExists('pdftk')) {
                $pageRanges = implode(' ', $validPageNumbers);
                $command = "pdftk \"$fullPath\" cat $pageRanges output \"$outputFullPath\"";
                exec($command, $output, $returnCode);
                $success = ($returnCode === 0 && file_exists($outputFullPath));
            }
            
            // If pdftk fails, try qpdf
            if (!$success && $this->commandExists('qpdf')) {
                $pageRanges = $this->buildPageRangeString($validPageNumbers);
                $command = "qpdf --empty --pages \"$fullPath\" $pageRanges -- \"$outputFullPath\"";
                exec($command, $output, $returnCode);
                $success = ($returnCode === 0 && file_exists($outputFullPath));
            }
            
            // If external tools failed, use PHP-based solution
            if (!$success) {
                $success = $this->extractPagesPHP($fullPath, $outputFullPath, $validPageNumbers);
            }
            
            if (!$success) {
                throw new \Exception('Failed to process PDF file. All PDF processing methods failed.');
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Pages extracted successfully',
                'file' => '/storage/' . $outputPath,
                'filename' => 'extracted-document-' . date('YmdHis') . '.pdf',
                'extractedPages' => $validPageNumbers
            ]);
            
        } catch (\Exception $e) {
            \Log::error('PDF Page Extraction Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to extract pages: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Extract pages from a PDF file using PHP (FPDI/FPDF)
     *
     * @param string $inputPath
     * @param string $outputPath
     * @param array $pagesToExtract
     * @return bool
     */
    private function extractPagesPHP($inputPath, $outputPath, $pagesToExtract)
    {
        try {
            // Validate input file exists
            if (!file_exists($inputPath)) {
                \Log::error('PDF input file does not exist', ['path' => $inputPath]);
                return false;
            }
            
            // Ensure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                if (!mkdir($outputDir, 0755, true)) {
                    \Log::error('Failed to create output directory', ['dir' => $outputDir]);
                    return false;
                }
            }
            
            // Create new PDF document
            $pdf = new Fpdi();
            
            // Set source file and handle potential errors
            try {
                $pageCount = $pdf->setSourceFile($inputPath);
                \Log::debug('Source PDF loaded successfully for extraction', [
                    'path' => $inputPath,
                    'page_count' => $pageCount
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to load PDF source file for extraction', [
                    'path' => $inputPath, 
                    'error' => $e->getMessage()
                ]);
                return false;
            }
            
            // Check if we have valid pages to extract
            if (empty($pagesToExtract)) {
                \Log::error('No pages to extract specified');
                return false;
            }
            
            // Log pages we're extracting
            \Log::debug('Processing PDF to extract specific pages', [
                'pages_to_extract' => $pagesToExtract,
                'total_pages' => $pageCount
            ]);
            
            // Sort pages to keep
            sort($pagesToExtract);
            
            // Track if we successfully added any pages
            $addedPages = 0;
            
            // Loop through pages to extract and add them to new PDF
            foreach ($pagesToExtract as $pageNumber) {
                // Skip if page doesn't exist
                if ($pageNumber > $pageCount || $pageNumber < 1) {
                    \Log::warning('Skipping invalid page number for extraction', ['page' => $pageNumber]);
                    continue;
                }
                
                try {
                    // Import page
                    $template = $pdf->importPage($pageNumber);
                    $size = $pdf->getTemplateSize($template);
                    
                    // Add page with same orientation and size
                    $pdf->AddPage(
                        $size['width'] > $size['height'] ? 'L' : 'P', 
                        [$size['width'], $size['height']]
                    );
                    
                    // Use the imported page
                    $pdf->useTemplate($template);
                    $addedPages++;
                } catch (\Exception $e) {
                    \Log::warning('Failed to import page for extraction', [
                        'page' => $pageNumber,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with other pages
                    continue;
                }
            }
            
            // Check if we successfully added any pages
            if ($addedPages === 0) {
                \Log::error('Failed to add any pages to the extracted PDF');
                return false;
            }
            
            try {
                // Output the new PDF
                $pdf->Output($outputPath, 'F');
                
                // Verify the file was created and has content
                if (file_exists($outputPath) && filesize($outputPath) > 0) {
                    \Log::debug('Extracted PDF created successfully', [
                        'path' => $outputPath,
                        'size' => filesize($outputPath),
                        'pages_added' => $addedPages
                    ]);
                    return true;
                } else {
                    \Log::error('PDF extraction failed or resulted in empty file', [
                        'path' => $outputPath,
                        'exists' => file_exists($outputPath),
                        'size' => file_exists($outputPath) ? filesize($outputPath) : 0
                    ]);
                    return false;
                }
            } catch (\Exception $e) {
                \Log::error('Error saving extracted PDF file', [
                    'path' => $outputPath,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('PHP PDF Extraction Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
} 