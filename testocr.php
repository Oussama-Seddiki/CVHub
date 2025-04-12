<?php
// Test Tesseract OCR Integration

echo "Testing Tesseract OCR Integration\n";

// المسار المباشر إلى Tesseract
$tesseractPath = "\"C:\\Program Files\\Tesseract-OCR\\tesseract.exe\"";

// التحقق من وجود Tesseract
echo "Checking Tesseract installation...\n";
$output = [];
$returnVar = 0;

exec("$tesseractPath --version", $output, $returnVar);

if ($returnVar === 0) {
    echo "Tesseract is installed and accessible.\n";
    echo "Version information:\n";
    echo implode("\n", $output) . "\n\n";
} else {
    echo "ERROR: Could not access Tesseract. Return code: $returnVar\n";
    die();
}

// التحقق من اللغات المتاحة
echo "Checking available languages...\n";
$output = [];
exec("$tesseractPath --list-langs", $output, $returnVar);

if ($returnVar === 0) {
    echo "Available languages:\n";
    
    $arabicFound = false;
    $englishFound = false;
    
    foreach ($output as $line) {
        if (trim($line) === 'ara') {
            $arabicFound = true;
        }
        if (trim($line) === 'eng') {
            $englishFound = true;
        }
    }
    
    echo "Arabic language support: " . ($arabicFound ? "YES" : "NO") . "\n";
    echo "English language support: " . ($englishFound ? "YES" : "NO") . "\n";
} else {
    echo "ERROR: Could not retrieve language list. Return code: $returnVar\n";
}

// تجربة تسجيل مسار Tesseract في ملف لاستخدامه في التطبيق
$configFile = __DIR__ . '/storage/app/tesseract_path.txt';
$configDir = dirname($configFile);

if (!is_dir($configDir)) {
    mkdir($configDir, 0755, true);
}

file_put_contents($configFile, "C:\\Program Files\\Tesseract-OCR\\tesseract.exe");
echo "Tesseract path saved to: $configFile\n";

echo "\nTest completed successfully.\n"; 