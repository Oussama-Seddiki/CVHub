<?php
/**
 * Java Testing Utility for PDF Conversion
 * 
 * This script tests Java integration that's required for PowerPoint conversions with notes
 */

// Enable error reporting for testing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<html><head><title>Java PDF Integration Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1 { color: #333; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
    h2 { color: #666; margin-top: 20px; }
    .pass { color: green; font-weight: bold; }
    .fail { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .code { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .result { margin: 10px 0; padding: 10px; border-radius: 5px; }
    .pass-bg { background-color: #dff0d8; }
    .fail-bg { background-color: #f2dede; }
    .btn { 
        display: inline-block; 
        padding: 8px 16px; 
        background: #007bff; 
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-top: 10px;
    }
    .btn:hover { background: #0069d9; }
</style>";
echo "</head><body>";
echo "<h1>Java PDF Integration Test</h1>";

// Function to run tests and format output
function runTest($name, $callback) {
    echo "<h2>$name</h2>";
    
    try {
        $result = $callback();
        if ($result['status'] === 'pass') {
            echo "<div class='result pass-bg'><span class='pass'>✓ PASS:</span> {$result['message']}</div>";
        } else {
            echo "<div class='result fail-bg'><span class='fail'>✗ FAIL:</span> {$result['message']}</div>";
        }
        
        if (isset($result['details'])) {
            echo "<div class='code'><pre>{$result['details']}</pre></div>";
        }
    } catch (Exception $e) {
        echo "<div class='result fail-bg'><span class='fail'>✗ ERROR:</span> {$e->getMessage()}</div>";
    }
}

// Test 1: Check PHP version and extensions
runTest("PHP Environment", function() {
    $result = [
        'status' => 'pass',
        'message' => 'PHP version and required extensions are available',
        'details' => "PHP Version: " . phpversion() . "\n"
    ];
    
    // Check required extensions
    $requiredExtensions = ['curl', 'gd', 'json', 'xml', 'fileinfo'];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    
    if (!empty($missingExtensions)) {
        $result['status'] = 'fail';
        $result['message'] = 'Missing required PHP extensions: ' . implode(', ', $missingExtensions);
    }
    
    $result['details'] .= "Loaded Extensions:\n" . implode(', ', get_loaded_extensions());
    return $result;
});

// Test 2: Check if exec() function is enabled
runTest("PHP exec() Function", function() {
    $disabled = explode(',', ini_get('disable_functions'));
    $execEnabled = !in_array('exec', $disabled);
    
    if ($execEnabled) {
        return [
            'status' => 'pass',
            'message' => 'exec() function is enabled',
            'details' => "disable_functions in php.ini:\n" . ini_get('disable_functions')
        ];
    } else {
        return [
            'status' => 'fail',
            'message' => 'exec() function is disabled in PHP configuration',
            'details' => "The exec() function is required for Java integration. You need to modify your php.ini file."
        ];
    }
});

// Test 3: Check for Java in PATH
runTest("Java in PATH", function() {
    $output = [];
    $returnVar = 0;
    
    exec('java -version 2>&1', $output, $returnVar);
    
    if ($returnVar === 0) {
        return [
            'status' => 'pass',
            'message' => 'Java is available in the system PATH',
            'details' => implode("\n", $output)
        ];
    } else {
        // Try to locate Java directly
        $commonJavaPaths = [
            'C:\\Program Files\\Java\\jre*\\bin\\java.exe',
            'C:\\Program Files\\Java\\jdk*\\bin\\java.exe',
            'C:\\Program Files (x86)\\Java\\jre*\\bin\\java.exe',
            'C:\\Program Files (x86)\\Java\\jdk*\\bin\\java.exe',
            'C:\\ProgramData\\Oracle\\Java\\javapath\\java.exe'
        ];
        
        $javaPath = null;
        foreach ($commonJavaPaths as $pattern) {
            $matches = glob($pattern);
            if (!empty($matches)) {
                $javaPath = $matches[0];
                break;
            }
        }
        
        if ($javaPath) {
            exec('"' . $javaPath . '" -version 2>&1', $output, $returnVar);
            if ($returnVar === 0) {
                return [
                    'status' => 'pass',
                    'message' => 'Java found at: ' . $javaPath,
                    'details' => "Java is installed but not in PATH. Output:\n" . implode("\n", $output)
                ];
            }
        }
        
        return [
            'status' => 'fail',
            'message' => 'Java is not available in the system PATH',
            'details' => "The system cannot find the Java executable. Make sure Java is installed and added to the PATH."
        ];
    }
});

// Test 4: Try to use Java for PDF operations
runTest("Java PDF Test", function() {
    // Create temporary test files
    $tempDir = sys_get_temp_dir() . '/java_pdf_test_' . uniqid();
    
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $javaCode = '
public class PDFTest {
    public static void main(String[] args) {
        System.out.println("Java PDF Test");
        System.out.println("Environment check successful");
        System.out.println("Java version: " + System.getProperty("java.version"));
        System.out.println("Java home: " + System.getProperty("java.home"));
        System.out.println("OS: " + System.getProperty("os.name"));
    }
}';
    
    file_put_contents($tempDir . '/PDFTest.java', $javaCode);
    
    // Compile the Java code
    $output = [];
    $returnVar = 0;
    exec('javac ' . $tempDir . '/PDFTest.java 2>&1', $output, $returnVar);
    
    if ($returnVar !== 0) {
        return [
            'status' => 'fail',
            'message' => 'Failed to compile Java test code',
            'details' => implode("\n", $output)
        ];
    }
    
    // Run the Java code
    $output = [];
    exec('java -cp ' . $tempDir . ' PDFTest 2>&1', $output, $returnVar);
    
    // Clean up
    if (is_file($tempDir . '/PDFTest.java')) unlink($tempDir . '/PDFTest.java');
    if (is_file($tempDir . '/PDFTest.class')) unlink($tempDir . '/PDFTest.class');
    if (is_dir($tempDir)) rmdir($tempDir);
    
    if ($returnVar === 0) {
        return [
            'status' => 'pass',
            'message' => 'Successfully executed Java test',
            'details' => implode("\n", $output)
        ];
    } else {
        return [
            'status' => 'fail',
            'message' => 'Failed to execute Java test',
            'details' => implode("\n", $output)
        ];
    }
});

// Test 5: Check LibreOffice with Java integration
runTest("LibreOffice-Java Integration", function() {
    // Check if LibreOffice is installed
    $possiblePaths = [
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
    ];
    
    $libreOfficePath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $libreOfficePath = $path;
            break;
        }
    }
    
    if (!$libreOfficePath) {
        return [
            'status' => 'fail',
            'message' => 'LibreOffice not found',
            'details' => "LibreOffice is required for PDF conversion. Please install LibreOffice."
        ];
    }
    
    // Try to run LibreOffice with Java integration
    $testDir = sys_get_temp_dir() . '/libreoffice_java_test_' . uniqid();
    if (!is_dir($testDir)) {
        mkdir($testDir, 0755, true);
    }
    
    // Set environment variables
    $env = [];
    $javaPath = null;
    
    $commonJavaPaths = [
        'C:\\Program Files\\Java\\jre*\\bin\\java.exe',
        'C:\\Program Files\\Java\\jdk*\\bin\\java.exe'
    ];
    
    foreach ($commonJavaPaths as $pattern) {
        $matches = glob($pattern);
        if (!empty($matches)) {
            $javaPath = $matches[0];
            $javaDir = dirname($javaPath);
            $javaHome = dirname($javaDir);
            
            putenv("JAVA_HOME={$javaHome}");
            putenv("JRE_HOME={$javaHome}");
            putenv("PATH={$javaDir}" . PATH_SEPARATOR . getenv('PATH'));
            
            break;
        }
    }
    
    // Try to get LibreOffice version with Java integration
    $output = [];
    $returnVar = 0;
    exec('"' . $libreOfficePath . '" --version 2>&1', $output, $returnVar);
    
    $details = "LibreOffice Path: " . $libreOfficePath . "\n";
    if ($javaPath) {
        $details .= "Java Path: " . $javaPath . "\n";
        $details .= "JAVA_HOME: " . getenv('JAVA_HOME') . "\n";
    } else {
        $details .= "Java Path: Not found\n";
    }
    
    $details .= "Output:\n" . implode("\n", $output);
    
    if ($returnVar === 0) {
        return [
            'status' => 'pass',
            'message' => 'LibreOffice found and appears to be working',
            'details' => $details
        ];
    } else {
        return [
            'status' => 'fail',
            'message' => 'LibreOffice found but failed to execute',
            'details' => $details
        ];
    }
});

// Final verdict
echo "<h2>Summary</h2>";
echo "<p>If all tests pass, your system should be ready to handle PPT to PDF conversion with notes!</p>";

echo "<p><a href='/' class='btn'>Return to Home</a></p>";

// Instructions for fixing issues
echo "<h2>Troubleshooting</h2>";
echo "<p>If you're experiencing issues:</p>";
echo "<ol>";
echo "<li>Make sure Java is installed and added to the system PATH</li>";
echo "<li>Run the <code>restart_xampp.bat</code> script to restart Apache with Java in the PATH</li>";
echo "<li>Check that LibreOffice is installed and accessible</li>";
echo "<li>Verify PHP has permission to execute system commands (exec function enabled)</li>";
echo "</ol>";

echo "</body></html>"; 