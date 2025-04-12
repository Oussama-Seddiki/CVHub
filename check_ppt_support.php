<?php
// Set correct path to autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Run a simple check for LibreOffice
echo "LibreOffice Check\n";
echo "----------------\n";

// Check possible LibreOffice paths
$libreOfficePaths = [
    'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
    'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
    'C:\\Program Files\\LibreOffice\\program\\soffice.bin',
    'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.bin',
    'C:\\Program Files\\OpenOffice\\program\\soffice.exe',
    'C:\\Program Files (x86)\\OpenOffice\\program\\soffice.exe'
];

$libreOfficeFound = false;
$libreOfficePath = '';

foreach ($libreOfficePaths as $path) {
    if (file_exists($path)) {
        $libreOfficeFound = true;
        $libreOfficePath = $path;
        echo "LibreOffice found at: $path\n";
        
        // Try to get version information
        try {
            $command = escapeshellcmd("\"$path\" --version");
            $output = shell_exec($command);
            echo "Version info: " . ($output ? trim($output) : "Unknown") . "\n";
        } catch (Exception $e) {
            echo "Error getting version: " . $e->getMessage() . "\n";
        }
        
        break;
    }
}

if (!$libreOfficeFound) {
    echo "LibreOffice not found in common locations\n";
}

// Check Java installation
echo "\nJava Check\n";
echo "----------\n";

$javaOutput = [];
$javaInstalled = false;

try {
    exec('java -version 2>&1', $javaOutput, $returnCode);
    $javaInstalled = ($returnCode === 0);
    
    echo "Java return code: $returnCode\n";
    if ($javaInstalled) {
        echo "Java installed:\n" . implode("\n", $javaOutput) . "\n";
    } else {
        echo "Java not installed or not in PATH\n";
    }
} catch (Exception $e) {
    echo "Error checking Java: " . $e->getMessage() . "\n";
}

// Check temporary directory
echo "\nDirectory Checks\n";
echo "---------------\n";
$tempDir = sys_get_temp_dir();
$storageDir = __DIR__ . '/storage/app/public';

echo "Temp directory: $tempDir\n";
echo "Temp directory writable: " . (is_writable($tempDir) ? 'Yes' : 'No') . "\n";
echo "Storage directory: $storageDir\n";
echo "Storage directory writable: " . (is_writable($storageDir) ? 'Yes' : 'No') . "\n";

// Summary
echo "\nSummary\n";
echo "-------\n";
echo "Base conversion support: " . ($libreOfficeFound ? 'Yes' : 'No') . "\n";
echo "Quality settings support: " . ($libreOfficeFound ? 'Yes' : 'No') . "\n";
echo "Include notes support: " . (($libreOfficeFound && $javaInstalled) ? 'Yes' : 'No') . "\n";

// If LibreOffice is found, try to run a simple test command to confirm it works
if ($libreOfficeFound) {
    echo "\nTesting LibreOffice Command\n";
    echo "--------------------------\n";
    
    // Create a test command 
    $testCommand = "\"$libreOfficePath\" --help";
    echo "Running: $testCommand\n";
    
    try {
        $output = shell_exec($testCommand);
        if ($output) {
            echo "Command executed successfully\n";
        } else {
            echo "Command executed but returned no output\n";
        }
    } catch (Exception $e) {
        echo "Error running LibreOffice: " . $e->getMessage() . "\n";
    }
} 