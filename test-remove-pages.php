<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\Pdf\Editors\RemovePagesService;
use setasign\Fpdi\Tcpdf\Fpdi;

// Set file paths
$testPdfPath = __DIR__ . '/tests/test.pdf';
$outputPath = __DIR__ . '/tests/test_removed_pages.pdf';

// Create test directory if it doesn't exist
if (!is_dir(__DIR__ . '/tests')) {
    mkdir(__DIR__ . '/tests', 0755, true);
}

// Create a test PDF if it doesn't exist
if (!file_exists($testPdfPath)) {
    // Use FPDI to create a sample 3-page PDF
    $pdf = new Fpdi();
    
    // Page 1
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 16);
    $pdf->Cell(0, 10, 'This is page 1', 0, 1, 'C');
    
    // Page 2
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 16);
    $pdf->Cell(0, 10, 'This is page 2', 0, 1, 'C');
    
    // Page 3
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 16);
    $pdf->Cell(0, 10, 'This is page 3', 0, 1, 'C');
    
    $pdf->Output($testPdfPath, 'F');
    echo "Created test PDF with 3 pages at {$testPdfPath}\n";
}

echo "Input file: {$testPdfPath}\n";
echo "Output file: {$outputPath}\n\n";

// Create the page removal service
$service = new RemovePagesService();

// Set up options to remove page 2
$options = [
    'pages' => '2', // Remove page 2
    'metadata' => [
        'title' => 'Test PDF with pages removed',
        'author' => 'Test Script'
    ]
];

echo "Attempting to remove page 2 from the PDF...\n";

try {
    // Process the PDF to remove specified pages
    $result = $service->process($testPdfPath, $outputPath, $options);
    
    // Get information about the operation
    $info = $service->getInfo();
    
    echo "Process result: " . ($result ? "SUCCESS" : "FAILURE") . "\n\n";
    
    // Output detailed information
    echo "Service info:\n";
    print_r($info);
    
    if ($result && file_exists($outputPath)) {
        echo "\nOutput file created successfully at {$outputPath}\n";
        echo "File size: " . filesize($outputPath) . " bytes\n";
    } else {
        echo "\nFailed to create output file\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} 