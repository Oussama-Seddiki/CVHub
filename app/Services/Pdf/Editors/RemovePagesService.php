<?php

namespace App\Services\Pdf\Editors;

use App\Services\Pdf\PdfInterface;
use setasign\Fpdi\Tcpdf\Fpdi;

class RemovePagesService implements PdfInterface
{
    /**
     * Information about the operation
     * 
     * @var array
     */
    protected $info = [];
    
    /**
     * Process a PDF file to remove specific pages
     *
     * @param string $inputPath Path to input file
     * @param string $outputPath Path to output file
     * @param array $options Processing options
     * @return bool Success or failure
     */
    public function process(string $inputPath, string $outputPath, array $options = []): bool
    {
        $this->info = [];
        
        try {
            // Validate input file
            if (!file_exists($inputPath)) {
                throw new \InvalidArgumentException("Input file not found: $inputPath");
            }
            
            // Make sure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            // Get page ranges to remove
            if (!isset($options['pages']) || empty($options['pages'])) {
                throw new \InvalidArgumentException("No pages specified for removal");
            }
            
            // Debug information
            $debugInfo = [
                'input_file' => $inputPath,
                'output_file' => $outputPath,
                'options' => $options
            ];
            
            $pagesToRemove = $this->parsePageRanges($options['pages']);
            $debugInfo['parsed_pages_to_remove'] = $pagesToRemove;
            
            // Create a new PDF document using FPDI
            $pdf = new Fpdi();
            
            // Set metadata if provided
            if (isset($options['metadata'])) {
                if (isset($options['metadata']['title'])) {
                    $pdf->SetTitle($options['metadata']['title']);
                }
                if (isset($options['metadata']['author'])) {
                    $pdf->SetAuthor($options['metadata']['author']);
                }
                if (isset($options['metadata']['subject'])) {
                    $pdf->SetSubject($options['metadata']['subject']);
                }
                if (isset($options['metadata']['keywords'])) {
                    $pdf->SetKeywords($options['metadata']['keywords']);
                }
            }
            
            try {
                // Get the number of pages
                $pageCount = $pdf->setSourceFile($inputPath);
                $debugInfo['total_pages'] = $pageCount;
            } catch (\Exception $e) {
                throw new \RuntimeException("Error reading source PDF: " . $e->getMessage());
            }
            
            // Validate that we're not removing all pages
            if (count($pagesToRemove) >= $pageCount) {
                throw new \InvalidArgumentException("Cannot remove all pages from the PDF");
            }
            
            // Convert the array of pages to remove to a lookup array for faster checks
            $pagesToRemoveLookup = array_flip($pagesToRemove);
            $debugInfo['pages_to_remove_lookup'] = $pagesToRemoveLookup;
            
            // Keep track of which pages we're keeping
            $keptPages = [];
            
            // Add all pages EXCEPT the ones to remove
            for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                // Skip if this page should be removed
                if (isset($pagesToRemoveLookup[$pageNum])) {
                    continue;
                }
                
                try {
                    // Import page
                    $templateId = $pdf->importPage($pageNum);
                    
                    // Get the size of the imported page
                    $size = $pdf->getTemplateSize($templateId);
                    
                    // Add a page with the same orientation and size
                    $pdf->AddPage(
                        $size['orientation'] === 'P' ? 'P' : 'L',
                        [$size['width'], $size['height']]
                    );
                    
                    // Use the imported page
                    $pdf->useTemplate($templateId);
                    
                    // Track which pages we kept
                    $keptPages[] = $pageNum;
                } catch (\Exception $e) {
                    // Log the error but continue processing other pages
                    $debugInfo['page_errors'][$pageNum] = $e->getMessage();
                }
            }
            
            $debugInfo['kept_pages'] = $keptPages;
            
            // Make sure we kept at least one page
            if (empty($keptPages)) {
                throw new \InvalidArgumentException("No pages would remain after removal");
            }
            
            // Output the new PDF with removed pages
            try {
                $pdf->Output($outputPath, 'F');
            } catch (\Exception $e) {
                throw new \RuntimeException("Error writing output PDF: " . $e->getMessage());
            }
            
            $this->info = [
                'success' => true,
                'message' => 'PDF pages removed successfully',
                'details' => [
                    'input_file' => $inputPath,
                    'output_file' => $outputPath,
                    'original_pages' => $pageCount,
                    'pages_removed' => count($pagesToRemove),
                    'removed_pages' => $pagesToRemove,
                    'kept_pages' => $keptPages,
                    'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                    'debug' => $debugInfo
                ]
            ];
            
            return true;
        } catch (\Exception $e) {
            $this->info = [
                'success' => false,
                'message' => 'Failed to remove PDF pages: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'debug' => $debugInfo ?? []
            ];
            
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
     * Parse page ranges from string like "1,3-5,7,10-12"
     *
     * @param string|array $input Page ranges string or array
     * @return array Array of page numbers
     */
    protected function parsePageRanges($input): array
    {
        $pages = [];
        
        // If already an array of page numbers, return as is
        if (is_array($input) && isset($input[0]) && is_numeric($input[0])) {
            return array_map('intval', $input);
        }
        
        // Convert to string if it's an array
        if (is_array($input)) {
            $input = implode(',', $input);
        }
        
        // Split by comma
        $parts = explode(',', $input);
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            // Check if it's a range (e.g., "3-7")
            if (preg_match('/^(\d+)-(\d+)$/', $part, $matches)) {
                $start = (int)$matches[1];
                $end = (int)$matches[2];
                
                // Add all pages in the range
                for ($i = $start; $i <= $end; $i++) {
                    $pages[] = $i;
                }
            } elseif (is_numeric($part)) {
                // Single page
                $pages[] = (int)$part;
            }
        }
        
        // Remove duplicates and sort
        $pages = array_unique($pages);
        sort($pages);
        
        return $pages;
    }
} 