<?php
/**
 * PDF Service Test Script
 * This script checks if the dependencies for PDF processing are available.
 */

// Headers for preventing caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: text/html; charset=utf-8');

// Check if Imagick is available
$imagickAvailable = extension_loaded('imagick');
$imagickVersion = $imagickAvailable ? phpversion('imagick') : 'Not available';

// Check if Ghostscript is installed
$ghostscriptAvailable = false;
$ghostscriptVersion = 'Not found';
// Try to detect Ghostscript
$gsCommands = ['gs', 'gswin32c', 'gswin64c'];
foreach ($gsCommands as $gsCommand) {
    $output = [];
    exec("$gsCommand --version 2>&1", $output, $returnCode);
    if ($returnCode === 0) {
        $ghostscriptAvailable = true;
        $ghostscriptVersion = $output[0] ?? 'Unknown version';
        break;
    }
}

// Check if FPDF/FPDI is available (look for the class files)
$fpdfAvailable = class_exists('FPDF') || file_exists(__DIR__ . '/../vendor/setasign/fpdf/fpdf.php');
$fpdiAvailable = class_exists('setasign\\Fpdi\\Fpdi') || file_exists(__DIR__ . '/../vendor/setasign/fpdi/src/Fpdi.php');

// Check if temp directory is writable
$tempDirWritable = is_writable(sys_get_temp_dir());
$storageTempDirExists = false;
$storageTempDirWritable = false;

$storageTempDir = __DIR__ . '/../storage/app/temp';
if (is_dir($storageTempDir)) {
    $storageTempDirExists = true;
    $storageTempDirWritable = is_writable($storageTempDir);
} else {
    // Try to create it
    $storageTempDirExists = @mkdir($storageTempDir, 0755, true);
    $storageTempDirWritable = $storageTempDirExists && is_writable($storageTempDir);
}

// Get PHP memory limit and upload max filesize
$memoryLimit = ini_get('memory_limit');
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$maxExecutionTime = ini_get('max_execution_time');

// Check if Laravel storage directory is writable
$storagePathWritable = is_writable(__DIR__ . '/../storage');
$storageAppPathWritable = is_writable(__DIR__ . '/../storage/app');
$storagePublicPathWritable = is_dir(__DIR__ . '/../storage/app/public') && is_writable(__DIR__ . '/../storage/app/public');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Service Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 20px;
        }
        .status {
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .status-table {
            width: 100%;
            border-collapse: collapse;
        }
        .status-table th, .status-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .status-table th {
            background-color: #f2f2f2;
        }
        .status-ok {
            color: green;
            font-weight: bold;
        }
        .status-error {
            color: red;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PDF Service Test</h1>
        <p>This page checks the availability of dependencies required for PDF processing services.</p>
        
        <h2>Service Dependencies</h2>
        <div class="status">
            <table class="status-table">
                <tr>
                    <th>Component</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
                <tr>
                    <td>Imagick Extension</td>
                    <td class="<?php echo $imagickAvailable ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $imagickAvailable ? 'Available' : 'Not Available'; ?>
                    </td>
                    <td><?php echo $imagickVersion; ?></td>
                </tr>
                <tr>
                    <td>Ghostscript</td>
                    <td class="<?php echo $ghostscriptAvailable ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $ghostscriptAvailable ? 'Available' : 'Not Available'; ?>
                    </td>
                    <td><?php echo $ghostscriptVersion; ?></td>
                </tr>
                <tr>
                    <td>FPDF Library</td>
                    <td class="<?php echo $fpdfAvailable ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $fpdfAvailable ? 'Available' : 'Not Available'; ?>
                    </td>
                    <td>Required for fallback PDF generation</td>
                </tr>
                <tr>
                    <td>FPDI Library</td>
                    <td class="<?php echo $fpdiAvailable ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $fpdiAvailable ? 'Available' : 'Not Available'; ?>
                    </td>
                    <td>Required for PDF manipulation</td>
                </tr>
            </table>
        </div>
        
        <h2>File System</h2>
        <div class="status">
            <table class="status-table">
                <tr>
                    <th>Path</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
                <tr>
                    <td>System Temp Directory</td>
                    <td class="<?php echo $tempDirWritable ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $tempDirWritable ? 'Writable' : 'Not Writable'; ?>
                    </td>
                    <td><?php echo sys_get_temp_dir(); ?></td>
                </tr>
                <tr>
                    <td>Storage Temp Directory</td>
                    <td class="<?php echo ($storageTempDirExists && $storageTempDirWritable) ? 'status-ok' : 'status-error'; ?>">
                        <?php 
                        if (!$storageTempDirExists) {
                            echo 'Does Not Exist';
                        } elseif (!$storageTempDirWritable) {
                            echo 'Not Writable';
                        } else {
                            echo 'Writable';
                        }
                        ?>
                    </td>
                    <td><?php echo $storageTempDir; ?></td>
                </tr>
                <tr>
                    <td>Storage Directory</td>
                    <td class="<?php echo $storagePathWritable ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $storagePathWritable ? 'Writable' : 'Not Writable'; ?>
                    </td>
                    <td><?php echo __DIR__ . '/../storage'; ?></td>
                </tr>
                <tr>
                    <td>Storage App Directory</td>
                    <td class="<?php echo $storageAppPathWritable ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $storageAppPathWritable ? 'Writable' : 'Not Writable'; ?>
                    </td>
                    <td><?php echo __DIR__ . '/../storage/app'; ?></td>
                </tr>
                <tr>
                    <td>Storage Public Directory</td>
                    <td class="<?php echo $storagePublicPathWritable ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $storagePublicPathWritable ? 'Writable' : 'Not Writable'; ?>
                    </td>
                    <td><?php echo __DIR__ . '/../storage/app/public'; ?></td>
                </tr>
            </table>
        </div>
        
        <h2>PHP Configuration</h2>
        <div class="status">
            <table class="status-table">
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Memory Limit</td>
                    <td><?php echo $memoryLimit; ?></td>
                </tr>
                <tr>
                    <td>Upload Max Filesize</td>
                    <td><?php echo $uploadMaxFilesize; ?></td>
                </tr>
                <tr>
                    <td>Post Max Size</td>
                    <td><?php echo $postMaxSize; ?></td>
                </tr>
                <tr>
                    <td>Max Execution Time</td>
                    <td><?php echo $maxExecutionTime; ?> seconds</td>
                </tr>
            </table>
        </div>
        
        <div class="footer">
            <p>Generated on <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html> 