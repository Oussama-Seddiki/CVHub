<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Services\Pdf\Editors\MergePdfService;
use App\Services\Storage\TemporaryStorage;

class TestPdfController extends Controller
{
    public function testPdfMerge()
    {
        try {
            // Create a temp directory to store our test files
            $tempDir = storage_path('app/temp/test_pdf_' . uniqid());
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            // Create two simple PDF files for testing
            $file1 = $this->createSimplePdf($tempDir . '/test1.pdf', 'Test PDF 1', 'This is test PDF 1');
            $file2 = $this->createSimplePdf($tempDir . '/test2.pdf', 'Test PDF 2', 'This is test PDF 2');
            
            // Create output file path
            $outputPath = $tempDir . '/merged.pdf';
            
            // Initialize the merge service
            $mergeService = new MergePdfService();
            
            // Process the merge
            $result = $mergeService->process('', $outputPath, [
                'files' => [$file1, $file2],
                'metadata' => [
                    'title' => 'Merged PDF',
                    'author' => 'Test System',
                    'subject' => 'PDF Testing',
                    'keywords' => 'test, pdf, merge'
                ]
            ]);
            
            // Get info from the service
            $info = $mergeService->getInfo();
            
            // Create a temporary storage service to get public URL
            $tempStorage = new TemporaryStorage();
            
            return response()->json([
                'success' => $result,
                'info' => $info,
                'download_url' => $tempStorage->getUrl($outputPath),
                'test_files' => [
                    'file1' => $file1,
                    'file2' => $file2,
                    'merged' => $outputPath
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    
    /**
     * Create a simple PDF file for testing
     * 
     * @param string $outputPath Path to save the PDF
     * @param string $title Title of the PDF
     * @param string $content Content to add to the PDF
     * @return string Path to the created PDF
     */
    private function createSimplePdf(string $outputPath, string $title, string $content): string
    {
        // Create a new PDF document
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('TCPDF');
        $pdf->SetAuthor('Test System');
        $pdf->SetTitle($title);
        $pdf->SetSubject('PDF Test');
        $pdf->SetKeywords('test, pdf');
        
        // Set default header and footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        
        // Add title
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        
        // Add content
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Ln(10);
        $pdf->MultiCell(0, 10, $content, 0, 'L');
        
        // Save the PDF
        $pdf->Output($outputPath, 'F');
        
        return $outputPath;
    }
}
