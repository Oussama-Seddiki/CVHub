<?php

namespace App\Services\Pdf\Editors;

use App\Services\Pdf\PdfInterface;
use setasign\Fpdi\Tcpdf\Fpdi;

class MergePdfService implements PdfInterface
{
    /**
     * Information about the operation
     * 
     * @var array
     */
    protected $info = [];
    
    /**
     * Process a PDF file merge operation
     *
     * @param string $inputPath Path to input file (not used directly in merge)
     * @param string $outputPath Path to output file
     * @param array $options Processing options
     * @return bool Success or failure
     */
    public function process(string $inputPath, string $outputPath, array $options = []): bool
    {
        $this->info = [];
        
        try {
            // Validate that files are provided in the options
            if (!isset($options['files']) || !is_array($options['files']) || count($options['files']) === 0) {
                throw new \InvalidArgumentException('No files provided for merge operation');
            }
            
            $files = $options['files'];
            
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
            
            // Process each file
            $totalPages = 0;
            foreach ($files as $file) {
                // Make sure the file exists
                if (!file_exists($file)) {
                    $this->info['warnings'][] = "File not found: $file";
                    continue;
                }
                
                try {
                    // Get the page count
                    $pageCount = $pdf->setSourceFile($file);
                    
                    // Import all pages
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        // Import page
                        $templateId = $pdf->importPage($pageNo);
                        
                        // Get the size of the imported page
                        $size = $pdf->getTemplateSize($templateId);
                        
                        // Add a page with the same orientation and size
                        $pdf->AddPage(
                            $size['orientation'] === 'P' ? 'P' : 'L',
                            [$size['width'], $size['height']]
                        );
                        
                        // Use the imported page
                        $pdf->useTemplate($templateId);
                        
                        $totalPages++;
                    }
                } catch (\Exception $e) {
                    $this->info['warnings'][] = "Error processing file $file: " . $e->getMessage();
                    continue;
                }
            }
            
            // Output the merged PDF
            $pdf->Output($outputPath, 'F');
            
            $this->info = [
                'success' => true,
                'message' => 'PDF files merged successfully',
                'details' => [
                    'input_files' => count($files),
                    'total_pages' => $totalPages,
                    'output_file' => $outputPath,
                    'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                ]
            ];
            
            return true;
        } catch (\Exception $e) {
            $this->info = [
                'success' => false,
                'message' => 'Failed to merge PDF files: ' . $e->getMessage(),
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
} 