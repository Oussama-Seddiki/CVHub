<?php

header('Content-Type: application/json');

$requiredExtensions = [
    'zip',
    'xml',
    'gd',
    'iconv',
    'simplexml',
    'xmlreader',
    'zlib'
];

$missingExtensions = [];
foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

echo json_encode([
    'missing_extensions' => $missingExtensions,
    'all_loaded' => empty($missingExtensions)
], JSON_PRETTY_PRINT); 