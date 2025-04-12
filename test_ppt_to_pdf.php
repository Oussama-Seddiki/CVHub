<?php
// Set correct path to autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Import the service
use App\Services\Pdf\Converters\PptToPdfService;
use Illuminate\Support\Facades\Log;

// Run a test conversion
echo "PPT to PDF Test Conversion\n";
echo "-------------------------\n";

// Set up file paths - make sure test.pptx exists in the storage/app/test directory
$inputDir = storage_path('app/test');
$outputDir = storage_path('app/public/converted');
$inputFile = $inputDir . '/test.pptx';
$outputFile = $outputDir . '/test_' . time() . '.pdf';

// Create directories if they don't exist
if (!is_dir($inputDir)) {
    mkdir($inputDir, 0755, true);
    echo "Created input directory: $inputDir\n";
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "Created output directory: $outputDir\n";
}

// Check if input file exists
if (!file_exists($inputFile)) {
    echo "⚠️ Input file not found: $inputFile\n";
    echo "Please place a test.pptx file in the $inputDir directory\n";
    exit(1);
}

echo "Input file: $inputFile\n";
echo "Output file: $outputFile\n";

// Test conversion options
$options = [
    'quality' => 'high',
    'include_notes' => true
];

echo "\nConversion options:\n";
echo "- Quality: " . $options['quality'] . "\n";
echo "- Include notes: " . ($options['include_notes'] ? 'Yes' : 'No') . "\n";

// Create the service and process
$service = new PptToPdfService();

// Set LibreOffice path if needed
$libreOfficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
if (file_exists($libreOfficePath)) {
    $service->setLibreOfficePath($libreOfficePath);
    echo "\nUsing LibreOffice at: $libreOfficePath\n";
}

// Start conversion
echo "\nStarting conversion...\n";

$startTime = microtime(true);
$result = $service->process($inputFile, $outputFile, $options);
$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

// Get conversion info
$info = $service->getInfo();

echo "\nConversion completed in $duration seconds\n";
echo "Result: " . ($result ? "Success ✓" : "Failed ✗") . "\n";

if ($result) {
    echo "Output file created: $outputFile\n";
    echo "Output file size: " . filesize($outputFile) . " bytes\n";
    
    // Generate public URL
    $publicPath = '/storage/converted/' . basename($outputFile);
    $url = 'http://localhost/CVHub/public' . $publicPath;
    echo "Public URL: $url\n";
} else {
    echo "Conversion failed. Details:\n";
    echo "Message: " . ($info['message'] ?? 'Unknown error') . "\n";
    if (isset($info['details'])) {
        echo "Error details: " . print_r($info['details'], true) . "\n";
    }
}

// Show conversion info
echo "\nConversion Info:\n";
echo json_encode($info, JSON_PRETTY_PRINT) . "\n"; 