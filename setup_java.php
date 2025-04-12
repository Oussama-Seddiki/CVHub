<?php
echo "Java Setup Helper\n";
echo "================\n\n";

// Check common Java installation paths on Windows
$commonJavaPaths = [
    'C:\\Program Files\\Java\\jre*\\bin\\java.exe',
    'C:\\Program Files\\Java\\jdk*\\bin\\java.exe',
    'C:\\Program Files (x86)\\Java\\jre*\\bin\\java.exe',
    'C:\\Program Files (x86)\\Java\\jdk*\\bin\\java.exe',
    'C:\\ProgramData\\Oracle\\Java\\javapath\\java.exe'
];

$javaFound = false;
$javaPath = '';

foreach ($commonJavaPaths as $pattern) {
    $matches = glob($pattern);
    if (!empty($matches)) {
        $javaFound = true;
        $javaPath = $matches[0];
        echo "✓ Found Java at: $javaPath\n";
        break;
    }
}

if (!$javaFound) {
    echo "✗ Could not find Java in common installation paths\n";
    echo "Please make sure Java is properly installed\n";
} else {
    // Get Java version using the found path
    echo "\nChecking Java version using the detected path:\n";
    $output = [];
    $return_var = 0;
    exec('"' . $javaPath . '" -version 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo "✓ Java is working properly\n";
        echo "Version information:\n";
        foreach ($output as $line) {
            echo "  $line\n";
        }
        
        // Suggest adding Java to PATH environment variable
        echo "\nTo make Java available system-wide, you need to add it to your PATH environment variable.\n";
        echo "1. Right-click on 'This PC' and select 'Properties'\n";
        echo "2. Click on 'Advanced system settings'\n";
        echo "3. Click on 'Environment Variables'\n";
        echo "4. Under 'System variables', find and edit 'Path'\n";
        echo "5. Add the path to Java bin directory: " . dirname($javaPath) . "\n";
        echo "6. Click 'OK' on all dialogs\n";
        echo "7. Restart your computer\n";
        
        // Configuration for Apache/PHP
        echo "\nFor Apache/PHP to detect Java, you might need to:\n";
        echo "1. Restart your XAMPP/Apache server\n";
        echo "2. Make sure the 'exec' function is enabled in php.ini\n";
        
        // Write a temporary batch file to help set the path
        $batchContent = "@echo off\necho Setting Java path temporarily...\n";
        $batchContent .= "set PATH=%PATH%;" . dirname($javaPath) . "\n";
        $batchContent .= "echo Path updated. You can now try running Java commands in this terminal session.\n";
        $batchContent .= "echo Try running: java -version\n";
        $batchContent .= "cmd\n";
        
        file_put_contents('set_java_path.bat', $batchContent);
        echo "\nCreated a temporary batch file 'set_java_path.bat' that you can run to temporarily set the Java path in a new command prompt.\n";
    } else {
        echo "✗ Error running Java\n";
        echo "Return code: $return_var\n";
    }
}

// Check LibreOffice detection
echo "\n\nChecking LibreOffice installation...\n";
$libreOfficePaths = [
    'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
    'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
    'C:\\Program Files\\LibreOffice*\\program\\soffice.exe',
    'C:\\Program Files (x86)\\LibreOffice*\\program\\soffice.exe'
];

$libreOfficeFound = false;
$libreOfficePath = '';

foreach ($libreOfficePaths as $pattern) {
    $matches = glob($pattern);
    if (!empty($matches)) {
        $libreOfficeFound = true;
        $libreOfficePath = $matches[0];
        echo "✓ Found LibreOffice at: $libreOfficePath\n";
        break;
    }
}

if (!$libreOfficeFound) {
    echo "✗ Could not find LibreOffice in common installation paths\n";
} else {
    echo "Both LibreOffice and Java are needed for full PDF conversion features.\n";
    echo "When both are properly installed, your PPT to PDF conversion with notes should work!\n";
} 