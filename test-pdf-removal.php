<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Pdf\Editors\RemovePagesService;

// Create an instance
$service = new RemovePagesService();

// Test PDF path 
$inputPath = __DIR__ . '/tests/test.pdf';
$outputPath = __DIR__ . '/tests/test_output.pdf';

// Create a test PDF if not exists
if (!file_exists($inputPath)) {
    echo "Creating test PDF...\n";
    $pdf = new setasign\Fpdi\Tcpdf\Fpdi();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Test Page 1', 0, 1);
    $pdf->AddPage();
    $pdf->Cell(0, 10, 'Test Page 2', 0, 1);
    $pdf->AddPage();
    $pdf->Cell(0, 10, 'Test Page 3', 0, 1);
    $pdf->Output($inputPath, 'F');
}

// Make sure tests directory exists
if (!is_dir(__DIR__ . '/tests')) {
    mkdir(__DIR__ . '/tests', 0755, true);
}

echo "Input file: $inputPath\n";
echo "Output file: $outputPath\n";

// Options for removing pages
$options = [
    'pages' => '2', // Remove page 2
];

try {
    echo "Processing PDF to remove pages...\n";
    $result = $service->process($inputPath, $outputPath, $options);
    
    if ($result) {
        echo "Success! Pages removed successfully.\n";
        echo "Result info: " . print_r($service->getInfo(), true) . "\n";
    } else {
        echo "Failed to remove pages.\n";
        echo "Error info: " . print_r($service->getInfo(), true) . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 