<?php
echo "Java Installation Check\n";
echo "=====================\n\n";

// Check Java version through command line
echo "Checking Java version:\n";
$output = [];
$return_var = 0;
exec('java -version 2>&1', $output, $return_var);

if ($return_var === 0) {
    echo "✓ Java is installed!\n";
    echo "Version information:\n";
    foreach ($output as $line) {
        echo "  $line\n";
    }
} else {
    echo "✗ Java not detected or error running java -version command\n";
    echo "Return code: $return_var\n";
}

// Additional checks to ensure Java is in the path
echo "\nChecking Java in system path:\n";
if (PHP_OS === 'WINNT' || PHP_OS === 'Windows' || PHP_OS === 'WIN32') {
    // Windows path check
    exec('where java', $path_output, $path_return);
    if ($path_return === 0) {
        echo "✓ Java found in path at: " . implode(", ", $path_output) . "\n";
    } else {
        echo "✗ Java not found in system path\n";
    }
} else {
    // Unix path check
    exec('which java', $path_output, $path_return);
    if ($path_return === 0) {
        echo "✓ Java found in path at: " . implode(", ", $path_output) . "\n";
    } else {
        echo "✗ Java not found in system path\n";
    }
}

echo "\nIf Java is installed but not detected, you may need to restart the web server or reboot your system.\n"; 