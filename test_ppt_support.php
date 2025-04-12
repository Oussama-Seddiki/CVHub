<?php
// Enable error reporting for testing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simulate the checkPptToPdfSupport method from PdfProcessingController

function checkJavaSupport() {
    echo "<h1>Java Support for PPT to PDF Conversion</h1>";
    
    // Check for LibreOffice
    $libreOfficeInstalled = false;
    $libreOfficePath = null;
    $possiblePaths = [
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $libreOfficeInstalled = true;
            $libreOfficePath = $path;
            echo "<p style='color:green'>✓ LibreOffice found at: $path</p>";
            break;
        }
    }
    
    if (!$libreOfficeInstalled) {
        echo "<p style='color:red'>✗ LibreOffice not found</p>";
    }
    
    // Check Java installation - direct paths
    $javaInstalled = false;
    $javaPath = null;
    $commonJavaPaths = [
        'C:\\Program Files\\Java\\jre*\\bin\\java.exe',
        'C:\\Program Files\\Java\\jdk*\\bin\\java.exe',
        'C:\\Program Files (x86)\\Java\\jre*\\bin\\java.exe',
        'C:\\Program Files (x86)\\Java\\jdk*\\bin\\java.exe',
        'C:\\ProgramData\\Oracle\\Java\\javapath\\java.exe'
    ];
    
    foreach ($commonJavaPaths as $pattern) {
        $matches = glob($pattern);
        if (!empty($matches)) {
            $javaPath = $matches[0];
            $javaInstalled = true;
            echo "<p style='color:green'>✓ Java found at: $javaPath</p>";
            
            // Try to get version
            $output = [];
            $returnVar = 0;
            exec('"' . $javaPath . '" -version 2>&1', $output, $returnVar);
            
            if ($returnVar === 0) {
                echo "<p>Java version: <pre>" . implode("\n", $output) . "</pre></p>";
            }
            break;
        }
    }
    
    // Check Java via PATH
    if (!$javaInstalled) {
        $output = [];
        $returnVar = 0;
        exec('java -version 2>&1', $output, $returnVar);
        
        if ($returnVar === 0) {
            $javaInstalled = true;
            echo "<p style='color:green'>✓ Java found in PATH</p>";
            echo "<p>Java version: <pre>" . implode("\n", $output) . "</pre></p>";
        } else {
            echo "<p style='color:red'>✗ Java not found in PATH or via direct paths</p>";
        }
    }
    
    // Testing environment variables
    echo "<h2>Environment Variables</h2>";
    echo "<p>JAVA_HOME: " . (getenv('JAVA_HOME') ?: 'Not set') . "</p>";
    echo "<p>JRE_HOME: " . (getenv('JRE_HOME') ?: 'Not set') . "</p>";
    echo "<p>PATH: " . (getenv('PATH') ?: 'Not set') . "</p>";
    
    // Set environment variables
    if ($javaPath) {
        $javaDir = dirname($javaPath);
        $javaHome = dirname($javaDir);
        
        putenv("JAVA_HOME=$javaHome");
        putenv("JRE_HOME=$javaHome");
        putenv("PATH=$javaDir" . PATH_SEPARATOR . getenv('PATH'));
        
        echo "<p style='color:blue'>Set environment variables:</p>";
        echo "<p>JAVA_HOME: " . getenv('JAVA_HOME') . "</p>";
        echo "<p>JRE_HOME: " . getenv('JRE_HOME') . "</p>";
    }
    
    // Summary
    echo "<h2>Support Summary</h2>";
    
    if ($libreOfficeInstalled && $javaInstalled) {
        echo "<p style='color:green; font-size: 18px;'>✓ Your system appears ready for PPT to PDF conversion with notes!</p>";
        echo "<p>All requirements are met:</p>";
        echo "<ul>";
        echo "<li>LibreOffice: " . ($libreOfficeInstalled ? '✓ Installed' : '✗ Not found') . "</li>";
        echo "<li>Java: " . ($javaInstalled ? '✓ Installed' : '✗ Not found') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color:red; font-size: 18px;'>✗ Your system is missing some requirements for full PPT to PDF support</p>";
        echo "<p>Check these requirements:</p>";
        echo "<ul>";
        echo "<li>LibreOffice: " . ($libreOfficeInstalled ? '✓ Installed' : '✗ Not found') . "</li>";
        echo "<li>Java: " . ($javaInstalled ? '✓ Installed' : '✗ Not found') . "</li>";
        echo "</ul>";
    }
    
    // Return to application
    echo "<p style='margin-top: 20px;'><a href='/CVHub' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Return to Application</a></p>";
}

// Run the check
checkJavaSupport(); 