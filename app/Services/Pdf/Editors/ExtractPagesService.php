<?php

namespace App\Services\Pdf\Editors;

use App\Services\Pdf\PdfInterface;
use setasign\Fpdi\Tcpdf\Fpdi;

class ExtractPagesService implements PdfInterface
{
    /**
     * Information about the operation
     * 
     * @var array
     */
    protected $info = [];
    
    /**
     * Process a PDF file to extract specific pages
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
            
            // Get page ranges
            if (!isset($options['pages']) || empty($options['pages'])) {
                throw new \InvalidArgumentException("No pages specified for extraction");
            }
            
            $pages = $this->parsePageRanges($options['pages']);
            
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
            
            // Get the number of pages
            $pageCount = $pdf->setSourceFile($inputPath);
            
            // Validate page ranges
            $validPages = [];
            foreach ($pages as $page) {
                if ($page > 0 && $page <= $pageCount) {
                    $validPages[] = $page;
                } else {
                    $this->info['warnings'][] = "Page $page is out of range (1-$pageCount)";
                }
            }
            
            if (empty($validPages)) {
                throw new \InvalidArgumentException("No valid pages specified for extraction");
            }
            
            // Extract the specified pages
            foreach ($validPages as $page) {
                // Import page
                $templateId = $pdf->importPage($page);
                
                // Get the size of the imported page
                $size = $pdf->getTemplateSize($templateId);
                
                // Add a page with the same orientation and size
                $pdf->AddPage(
                    $size['orientation'] === 'P' ? 'P' : 'L',
                    [$size['width'], $size['height']]
                );
                
                // Use the imported page
                $pdf->useTemplate($templateId);
            }
            
            // Output the extracted pages
            $pdf->Output($outputPath, 'F');
            
            $this->info = [
                'success' => true,
                'message' => 'PDF pages extracted successfully',
                'details' => [
                    'input_file' => $inputPath,
                    'output_file' => $outputPath,
                    'pages_extracted' => count($validPages),
                    'pages' => $validPages,
                    'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                ]
            ];
            
            return true;
        } catch (\Exception $e) {
            $this->info = [
                'success' => false,
                'message' => 'Failed to extract PDF pages: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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