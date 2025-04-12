<?php

namespace App\Services\Pdf\Converters;

use App\Services\Pdf\PdfInterface;
use Symfony\Component\Process\Process;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf;
use App\Services\Storage\TemporaryStorage;
use Illuminate\Support\Facades\Log;

class ExcelToPdfService implements PdfInterface
{
    /**
     * Information about the operation
     * 
     * @var array
     */
    protected $info = [];
    
    /**
     * Path to the LibreOffice/OpenOffice executable
     */
    protected $libreOfficePath;
    
    private $temporaryStorage;
    
    /**
     * Create a new Excel to PDF converter instance
     * 
     * @param string|null $libreOfficePath Path to LibreOffice executable
     */
    public function __construct(?string $libreOfficePath = null, TemporaryStorage $temporaryStorage)
    {
        // Use provided path or try to auto-detect
        $this->libreOfficePath = $libreOfficePath ?? $this->detectLibreOfficePath();
        $this->temporaryStorage = $temporaryStorage;
    }
    
    /**
     * Process an Excel to PDF conversion
     *
     * @param string $inputPath Path to input Excel file
     * @param string $outputPath Path to output PDF file
     * @param array $options Processing options
     * @return bool Success or failure
     */
    public function process(string $inputPath, string $outputPath, array $options = []): bool
    {
        $this->info = [];
        $tempDir = null;
        
        try {
            // Validate input file
            if (!file_exists($inputPath)) {
                throw new \InvalidArgumentException("Input file not found: $inputPath");
            }
            
            // Check if file is readable
            if (!is_readable($inputPath)) {
                throw new \InvalidArgumentException("Input file not readable: $inputPath");
            }
            
            // Check file size
            $fileSize = filesize($inputPath);
            if ($fileSize === false || $fileSize === 0) {
                throw new \InvalidArgumentException("Input file is empty or corrupted: $inputPath (Size: " . ($fileSize === false ? 'unknown' : $fileSize) . ")");
            }
            
            \Log::debug('Excel to PDF conversion starting', [
                'input_path' => $inputPath,
                'output_path' => $outputPath,
                'file_size' => $fileSize,
                'file_exists' => file_exists($inputPath),
                'file_readable' => is_readable($inputPath),
                'options' => $options
            ]);
            
            // Make sure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                if (!mkdir($outputDir, 0755, true)) {
                    throw new \RuntimeException("Failed to create output directory: $outputDir");
                }
            }
            
            // Check if output directory is writable
            if (!is_writable($outputDir)) {
                \Log::warning('Output directory is not writable, attempting to fix permissions', [
                    'path' => $outputDir,
                    'permissions' => substr(sprintf('%o', fileperms($outputDir)), -4)
                ]);
                chmod($outputDir, 0755);
                if (!is_writable($outputDir)) {
                    throw new \RuntimeException("Output directory is not writable: $outputDir");
                }
            }
            
            // Ensure output file has PDF extension
            if (!preg_match('/\.pdf$/i', $outputPath)) {
                $outputPath .= '.pdf';
            }
            
            // Get temp directory for conversion
            $tempDir = sys_get_temp_dir() . '/excel_to_pdf_' . uniqid();
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    // If default temp dir fails, try creating in storage path
                    $tempDir = storage_path('app/temp/excel_to_pdf_' . uniqid());
                    if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true)) {
                        throw new \RuntimeException("Failed to create temp directory: $tempDir");
                    }
                }
            }
            
            // Check if temp directory is writable
            if (!is_writable($tempDir)) {
                \Log::warning('Temp directory is not writable, attempting to fix permissions', [
                    'path' => $tempDir,
                    'permissions' => substr(sprintf('%o', fileperms($tempDir)), -4)
                ]);
                chmod($tempDir, 0777);
                if (!is_writable($tempDir)) {
                    throw new \RuntimeException("Temp directory is not writable: $tempDir");
                }
            }
            
            // Copy input file to temp directory with error checking
            $safeFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($inputPath, PATHINFO_FILENAME));
            $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
            $tempInputFile = $tempDir . '/' . $safeFilename . '.' . $extension;
            
            if (!copy($inputPath, $tempInputFile)) {
                throw new \RuntimeException("Failed to copy input file to temp directory: $inputPath to $tempInputFile");
            }
            
            // Check if temp file was created successfully
            if (!file_exists($tempInputFile) || !is_readable($tempInputFile)) {
                throw new \RuntimeException("Failed to access temp file after copying: $tempInputFile");
            }
            
            // Log detailed environment information
            \Log::debug('Excel to PDF conversion environment', [
                'libre_office_path' => $this->libreOfficePath,
                'temp_dir' => $tempDir,
                'temp_dir_writable' => is_writable($tempDir),
                'output_dir' => $outputDir,
                'output_dir_writable' => is_writable($outputDir),
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'exec_enabled' => function_exists('exec'),
                'user' => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'unknown'
            ]);
            
            // Try different conversion methods in order of preference
            
            // 1. First try: Use PhpSpreadsheet with DomPDF/mPDF (preferred library method)
            \Log::info('Attempting to use PhpSpreadsheet with DomPDF for Excel to PDF conversion');
            
            // Configure DomPDF options if installed
            if (class_exists('\Dompdf\Dompdf')) {
                // Set DomPDF options for better rendering
                $dompdfOptions = new \Dompdf\Options();
                $dompdfOptions->set('isHtml5ParserEnabled', true);
                $dompdfOptions->set('isRemoteEnabled', true);
                $dompdfOptions->set('defaultFont', 'DejaVu Sans');
                
                // Try to increase memory limit if possible
                $dompdfOptions->set('memory_limit', '512M');
                
                // Register DomPDF options globally
                $dompdf = new \Dompdf\Dompdf($dompdfOptions);
                \Dompdf\Dompdf::setOptions($dompdfOptions);
            }
            
            if ($this->convertWithPhpSpreadsheet($tempInputFile, $outputPath, $options)) {
                try {
                    $publicPath = $this->storeInPublicStorage($outputPath);
                    $this->cleanupTempDir($tempDir);
                    
                    $this->info = [
                        'success' => true,
                        'message' => 'Excel file converted to PDF successfully using PhpSpreadsheet with DomPDF',
                        'output_path' => $publicPath,
                        'details' => [
                            'input_file' => $inputPath,
                            'output_file' => $outputPath,
                            'public_path' => $publicPath,
                            'quality' => $options['quality'] ?? 'standard',
                            'conversion_method' => 'phpspreadsheet_dompdf',
                            'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                        ]
                    ];
                    
                    return true;
                } catch (\Exception $storageError) {
                    \Log::error('Error storing PDF output', [
                        'error' => $storageError->getMessage(),
                        'trace' => $storageError->getTraceAsString()
                    ]);
                    // Continue to other methods if storage fails
                }
            } else {
                \Log::warning('PhpSpreadsheet conversion failed, trying other methods');
            }
            
            // 2. Second try: Use PowerShell conversion (on Windows)
            if (PHP_OS === 'WINNT' || PHP_OS === 'Windows' || PHP_OS === 'WIN32') {
                \Log::info('Attempting to use PowerShell conversion for Excel to PDF');
                if ($this->convertWithPowerShell($tempInputFile, $outputPath)) {
                    $publicPath = $this->storeInPublicStorage($outputPath);
                    $this->cleanupTempDir($tempDir);
                    
                    $this->info = [
                        'success' => true,
                        'message' => 'Excel file converted to PDF successfully using PowerShell',
                        'output_path' => $publicPath,
                        'details' => [
                            'input_file' => $inputPath,
                            'output_file' => $outputPath,
                            'public_path' => $publicPath,
                            'quality' => $options['quality'] ?? 'standard',
                            'conversion_method' => 'powershell',
                            'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                        ]
                    ];
                    
                    return true;
                } else {
                    \Log::warning('PowerShell conversion failed, trying other methods');
                }
            }
            
            // 3. Third try: Use LibreOffice for the conversion
            $libreOfficePath = $this->libreOfficePath;
            if (!$libreOfficePath) {
                \Log::info('LibreOffice path not set, attempting to detect...');
                $libreOfficePath = $this->findLibreOfficeExecutable();
                if ($libreOfficePath) {
                    $this->libreOfficePath = $libreOfficePath;
                    \Log::info('LibreOffice found at: ' . $libreOfficePath);
                } else {
                    \Log::warning('LibreOffice not found in common locations');
                }
            }
            
            if ($libreOfficePath) {
                \Log::info('Using LibreOffice for Excel to PDF conversion at: ' . $libreOfficePath);
                
                if ($this->convertWithLibreOffice($tempInputFile, $tempDir, $outputPath, $options)) {
                    $publicPath = $this->storeInPublicStorage($outputPath);
                    $this->cleanupTempDir($tempDir);
                    
                    $this->info = [
                        'success' => true,
                        'message' => 'Excel file converted to PDF successfully using LibreOffice',
                        'output_path' => $publicPath,
                        'details' => [
                            'input_file' => $inputPath,
                            'output_file' => $outputPath,
                            'public_path' => $publicPath,
                            'quality' => $options['quality'] ?? 'standard',
                            'conversion_method' => 'libreoffice',
                            'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                        ]
                    ];
                    
                    return true;
                } else {
                    \Log::warning('LibreOffice conversion failed, trying fallback methods');
                }
            } else {
                \Log::warning('LibreOffice not available, trying fallback methods');
            }
            
            // 4. Fourth try: COM automation (Windows only)
            if (PHP_OS === 'WINNT' || PHP_OS === 'Windows' || PHP_OS === 'WIN32') {
                \Log::info('Trying Excel to PDF conversion with COM automation');
                
                $comResult = $this->convertWithCOM($tempInputFile, $tempDir);
                if ($comResult && file_exists($comResult)) {
                    if (!rename($comResult, $outputPath)) {
                        // If rename fails, try copy+delete
                        if (copy($comResult, $outputPath)) {
                            @unlink($comResult);
                        } else {
                            throw new \RuntimeException("Failed to move converted file from $comResult to $outputPath");
                        }
                    }
                    
                    $publicPath = $this->storeInPublicStorage($outputPath);
                    $this->cleanupTempDir($tempDir);
                    
                    $this->info = [
                        'success' => true,
                        'message' => 'Excel file converted to PDF successfully using COM automation',
                        'output_path' => $publicPath,
                        'details' => [
                            'input_file' => $inputPath,
                            'output_file' => $outputPath,
                            'public_path' => $publicPath,
                            'quality' => $options['quality'] ?? 'standard',
                            'conversion_method' => 'com_automation',
                            'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                        ]
                    ];
                    
                    return true;
                } else {
                    \Log::warning('COM automation failed or not available');
                }
            }
            
            // If all methods failed
            $errorMessage = "All conversion methods failed. Please make sure required libraries are installed.";
            \Log::error($errorMessage);
            throw new \RuntimeException($errorMessage);
            
        } catch (\Exception $e) {
            \Log::error('Excel to PDF conversion error: ' . $e->getMessage(), [
                'input_path' => $inputPath,
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->info = [
                'success' => false,
                'message' => 'Failed to convert Excel file to PDF: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
            
            // Cleanup temp directory if it exists
            if ($tempDir && is_dir($tempDir)) {
                $this->cleanupTempDir($tempDir);
            }
            
            return false;
        }
    }
    
    /**
     * Store the PDF in public storage for web access
     *
     * @param string $pdfPath Path to the PDF file
     * @return string Relative path in storage
     */
    protected function storeInPublicStorage(string $pdfPath): string
    {
        try {
            // Create a unique filename with timestamp
            $filename = 'excel_' . time() . '_' . uniqid() . '.pdf';
            
            // Public storage directory
            $publicDir = storage_path('app/public/pdf');
            if (!is_dir($publicDir)) {
                if (!mkdir($publicDir, 0755, true)) {
                    \Log::error('Failed to create public directory', [
                        'path' => $publicDir,
                        'permissions' => substr(sprintf('%o', fileperms(storage_path('app/public'))), -4)
                    ]);
                    throw new \RuntimeException("Failed to create public directory: $publicDir");
                }
            }
            
            // Make sure the directory is writable
            if (!is_writable($publicDir)) {
                \Log::error('Public directory is not writable', [
                    'path' => $publicDir,
                    'permissions' => substr(sprintf('%o', fileperms($publicDir)), -4)
                ]);
                
                // Try to fix permissions
                chmod($publicDir, 0755);
                if (!is_writable($publicDir)) {
                    throw new \RuntimeException("Public directory is not writable: $publicDir");
                }
            }
            
            // Destination path
            $destPath = $publicDir . '/' . $filename;
            
            // Copy the file with error checking
            if (!copy($pdfPath, $destPath)) {
                \Log::error('Failed to copy PDF to public storage', [
                    'source' => $pdfPath,
                    'destination' => $destPath,
                    'source_exists' => file_exists($pdfPath),
                    'source_readable' => is_readable($pdfPath),
                    'destination_writable' => is_writable(dirname($destPath))
                ]);
                throw new \RuntimeException("Failed to copy PDF file to public storage");
            }
            
            // Verify file was copied successfully
            if (!file_exists($destPath)) {
                throw new \RuntimeException("Failed to verify file was copied to $destPath");
            }
            
            // Return the relative storage path
            return 'public/pdf/' . $filename;
        } catch (\Exception $e) {
            \Log::error('Error storing PDF in public storage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'pdfPath' => $pdfPath
            ]);
            
            // Return a placeholder error path that controllers can detect
            throw $e;
        }
    }
    
    /**
     * Get information about the operation
     *
     * @return array Information about current operation
     */
    public function getInfo(): array
    {
        return $this->info;
    }
    
    /**
     * Detect LibreOffice/OpenOffice path
     *
     * @return string|null The path if found, null otherwise
     */
    public function detectLibreOfficePath(): ?string
    {
        // Common paths on different operating systems
        $possiblePaths = [
            // Windows
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files\\OpenOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\OpenOffice\\program\\soffice.exe',
            
            // macOS
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
            '/Applications/OpenOffice.app/Contents/MacOS/soffice',
            
            // Linux
            '/usr/bin/libreoffice',
            '/usr/bin/soffice',
            '/usr/lib/libreoffice/program/soffice',
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Try to find using which command on Unix systems
        if (function_exists('exec')) {
            $output = [];
            $returnVar = 0;
            exec('which libreoffice 2>/dev/null', $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output[0])) {
                return $output[0];
            }
            
            exec('which soffice 2>/dev/null', $output, $returnVar);
            if ($returnVar === 0 && !empty($output[0])) {
                return $output[0];
            }
        }
        
        // Use expanded search if not found yet
        return $this->findLibreOfficeExecutable();
    }
    
    /**
     * Clean up temporary directory
     *
     * @param string $dir Directory to clean up
     * @return void
     */
    protected function cleanupTempDir(string $dir): void
    {
        if (is_dir($dir)) {
            // Delete all files in the directory
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            
            // Delete the directory itself
            rmdir($dir);
        }
    }
    
    /**
     * Find LibreOffice executable with expanded search
     *
     * @return string|null Path to LibreOffice executable
     */
    public function findLibreOfficeExecutable(): ?string
    {
        // Try to use where/which command first
        if (PHP_OS === 'WINNT' || PHP_OS === 'Windows' || PHP_OS === 'WIN32') {
            // Try various Windows commands
            $commands = [
                'where soffice.exe',
                'where libreoffice.exe',
                'dir /b /s "C:\\Program Files\\*soffice.exe*"',
                'dir /b /s "C:\\Program Files (x86)\\*soffice.exe*"',
            ];
            
            foreach ($commands as $command) {
                try {
                    \Log::debug('Trying to locate LibreOffice with: ' . $command);
                    $output = [];
                    $returnVar = 0;
                    exec($command, $output, $returnVar);
                    
                    if ($returnVar === 0 && !empty($output[0])) {
                        $path = trim($output[0]);
                        if (file_exists($path)) {
                            \Log::info('Found LibreOffice at: ' . $path);
                            return $path;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error while locating LibreOffice: ' . $e->getMessage());
                }
            }
            
            // Check additional Windows paths
            $windowsPaths = [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files\\LibreOffice 5\\program\\soffice.exe',
                'C:\\Program Files\\LibreOffice 6\\program\\soffice.exe',
                'C:\\Program Files\\LibreOffice 7\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice 5\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice 6\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice 7\\program\\soffice.exe',
                'C:\\Program Files\\OpenOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\OpenOffice\\program\\soffice.exe',
            ];
            
            foreach ($windowsPaths as $path) {
                if (file_exists($path)) {
                    \Log::info('Found LibreOffice at: ' . $path);
                    return $path;
                }
            }
        } else {
            // For Unix-like systems
            $unixCommands = [
                'which libreoffice',
                'which soffice',
                'which openoffice',
            ];
            
            foreach ($unixCommands as $command) {
                try {
                    $output = [];
                    $returnVar = 0;
                    exec($command . ' 2>/dev/null', $output, $returnVar);
                    
                    if ($returnVar === 0 && !empty($output[0])) {
                        $path = trim($output[0]);
                        if (file_exists($path)) {
                            return $path;
                        }
                    }
                } catch (\Exception $e) {
                    // Just continue to the next method
                }
            }
            
            // Check additional Unix paths
            $unixPaths = [
                '/usr/bin/libreoffice',
                '/usr/bin/soffice',
                '/usr/lib/libreoffice/program/soffice',
                '/usr/lib64/libreoffice/program/soffice',
                '/opt/libreoffice/program/soffice',
                '/usr/local/bin/libreoffice',
                '/usr/local/bin/soffice',
            ];
            
            foreach ($unixPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Convert Excel file to PDF using PhpSpreadsheet library
     *
     * @param string $inputFile Input Excel file
     * @param string $outputPath Output PDF file
     * @param array $options Conversion options
     * @return bool Success status
     */
    protected function convertWithPhpSpreadsheet(string $inputFile, string $outputPath, array $options = []): bool
    {
        try {
            Log::debug('Converting Excel to PDF using PhpSpreadsheet');

            // Load Excel file
            $spreadsheet = IOFactory::load($inputFile);

            // Configure PDF writer
            $writer = new Dompdf($spreadsheet);
            
            // Set PDF options
            $writer->setPaperSize($options['page_size'] ?? 'A4');
            $writer->setOrientation($options['orientation'] ?? 'portrait');

            // Save PDF
            $writer->save($outputPath);

            return true;
        } catch (\Exception $e) {
            Log::error('Error in PhpSpreadsheet conversion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Generate HTML from Excel data
     */
    protected function generateHtmlFromExcel(array $spreadsheet, array $options): string
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>';

        // Check if content contains Arabic
        $isArabic = $this->containsArabicText($spreadsheet);
        if ($isArabic) {
            $html = str_replace('<body>', '<body dir="rtl">', $html);
        }

        foreach ($spreadsheet as $sheetIndex => $sheet) {
            $html .= '<h2>Sheet ' . ($sheetIndex + 1) . '</h2>';
            $html .= '<table>';
            
            // Add headers
            if (!empty($sheet[0])) {
                $html .= '<thead><tr>';
                foreach ($sheet[0] as $header) {
                    $html .= '<th>' . htmlspecialchars($header) . '</th>';
                }
                $html .= '</tr></thead>';
            }

            // Add data rows
            $html .= '<tbody>';
            foreach (array_slice($sheet, 1) as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';
        return $html;
    }

    /**
     * Check if data contains Arabic text
     */
    protected function containsArabicText(array $data): bool
    {
        foreach ($data as $sheet) {
            foreach ($sheet as $row) {
                foreach ($row as $cell) {
                    if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{10E60}-\x{10E7F}\x{1EE00}-\x{1EEFF}]/u', $cell)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Convert Excel file to PDF using LibreOffice with enhanced settings
     *
     * @param string $inputFile Input Excel file path
     * @param string $outputDir Output directory
     * @param string $outputPath Final output PDF path
     * @param array $options Conversion options
     * @return bool Success status
     */
    protected function convertWithLibreOffice(string $inputFile, string $outputDir, string $outputPath, array $options = []): bool
    {
        try {
            \Log::debug('Converting Excel to PDF using LibreOffice', [
                'input_file' => $inputFile,
                'output_dir' => $outputDir,
                'output_path' => $outputPath,
                'options' => $options
            ]);
            
            if (!file_exists($inputFile)) {
                throw new \RuntimeException("Input file not found: $inputFile");
            }
            
            $libreOfficePath = $this->libreOfficePath;
            
            if (!$libreOfficePath) {
                throw new \RuntimeException("LibreOffice executable path not set");
            }
            
            // Make sure the output directory exists
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }
            
            // Get the settings for LibreOffice conversion
            $orientation = $options['orientation'] ?? 'portrait';
            $pageSize = $options['page_size'] ?? 'A4';
            $margins = $options['margins'] ?? 'normal';
            $worksheetOption = $options['worksheet_option'] ?? 'all';
            $fitTo = $options['fit_to'] ?? 'width';
            
            // Create a filter options file for LibreOffice
            $filterOptionsPath = $outputDir . '/filter_options.properties';
            $filterOptions = [
                "PageSize={$pageSize}",
                "Orientation={$orientation}",
                "Margins={$margins}",
            ];
            
            // Add worksheet options if set to active only
            if ($worksheetOption === 'active') {
                $filterOptions[] = "ActiveSheetOnly=true";
            }
            
            // Add fit to options
            if ($fitTo === 'width') {
                $filterOptions[] = "FitToWidth=true";
                $filterOptions[] = "FitToHeight=false";
            } elseif ($fitTo === 'height') {
                $filterOptions[] = "FitToWidth=false";
                $filterOptions[] = "FitToHeight=true";
            } elseif ($fitTo === 'page') {
                $filterOptions[] = "FitToWidth=true";
                $filterOptions[] = "FitToHeight=true";
            }
            
            // Write filter options to file
            file_put_contents($filterOptionsPath, implode("\n", $filterOptions));
            
            // Create temporary batch file for Windows execution
            $batchFilePath = $outputDir . '/convert.bat';
            $batchContent = '@echo off' . PHP_EOL;
            $batchContent .= 'set HOME=C:\\temp' . PHP_EOL;
            $batchContent .= 'set TEMP=C:\\temp' . PHP_EOL;
            $batchContent .= 'set TMP=C:\\temp' . PHP_EOL;
            $batchContent .= 'set APPDATA=C:\\temp\\LibreOffice_UserProfile' . PHP_EOL;
            $batchContent .= 'set USERPROFILE=C:\\temp\\LibreOffice_UserProfile' . PHP_EOL;
            $batchContent .= 'set LOCALAPPDATA=C:\\temp\\LibreOffice_UserProfile' . PHP_EOL;
            $batchContent .= 'cd /d "' . dirname($libreOfficePath) . '"' . PHP_EOL;
            $batchContent .= '"' . $libreOfficePath . '" --headless --nofirststartwizard --norestore -env:UserInstallation=file:///C:/temp/LibreOffice_UserProfile --convert-to pdf:calc_pdf_Export:' . str_replace('\\', '/', $filterOptionsPath) . ' --outdir "' . $outputDir . '" "' . $inputFile . '"' . PHP_EOL;
            file_put_contents($batchFilePath, $batchContent);
            
            // Make the batch file executable
            chmod($batchFilePath, 0755);
            
            // Execute the batch file
            \Log::debug('Executing batch file', ['path' => $batchFilePath, 'content' => $batchContent]);
            $output = [];
            $returnCode = 0;
            exec('cmd.exe /c "' . $batchFilePath . '" 2>&1', $output, $returnCode);
            
            \Log::debug('Batch file execution result', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output)
            ]);
            
            // Check if the command was successful
            if ($returnCode !== 0) {
                throw new \RuntimeException("LibreOffice conversion failed with return code $returnCode: " . implode("\n", $output));
            }
            
            // Check if the output file was created
            $outputFileName = pathinfo($inputFile, PATHINFO_FILENAME) . '.pdf';
            $tempOutputPath = $outputDir . '/' . $outputFileName;
            
            if (!file_exists($tempOutputPath)) {
                throw new \RuntimeException("Output file not created: $tempOutputPath");
            }
            
            // Move the file to the final destination
            if (!copy($tempOutputPath, $outputPath)) {
                throw new \RuntimeException("Failed to copy converted file from $tempOutputPath to $outputPath");
            }
            
            // Clean up temporary files
            @unlink($tempOutputPath);
            @unlink($batchFilePath);
            @unlink($filterOptionsPath);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error in LibreOffice conversion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Try to convert using COM automation on Windows
     *
     * @param string $inputFile Input Excel file
     * @param string $outputDir Output directory
     * @return string|null Path to converted file or null on failure
     */
    protected function convertWithCOM(string $inputFile, string $outputDir): ?string
    {
        try {
            if (!class_exists('COM')) {
                return null;
            }
            
            \Log::debug('Attempting Excel to PDF conversion using COM automation');
            
            // Create Excel COM object
            $excel = new \COM('Excel.Application');
            $excel->Visible = false;
            $excel->DisplayAlerts = false;
            
            // Open Excel file
            $workbook = $excel->Workbooks->Open($inputFile);
            
            // Set output path
            $outputFile = $outputDir . '/' . pathinfo($inputFile, PATHINFO_FILENAME) . '.pdf';
            
            // PDF format constant (xlTypePDF = 0)
            $workbook->ExportAsFixedFormat(0, $outputFile);
            
            // Close workbook and quit Excel
            $workbook->Close(false);
            $excel->Quit();
            
            // Release COM objects
            unset($workbook);
            unset($excel);
            
            return $outputFile;
        } catch (\Exception $e) {
            \Log::warning('COM automation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Convert Excel to PDF using PowerShell and Microsoft Excel (Windows-only)
     *
     * @param string $inputFile Input Excel file 
     * @param string $outputPath Output PDF file
     * @return bool Success or failure
     */
    protected function convertWithPowerShell(string $inputFile, string $outputPath): bool
    {
        try {
            \Log::debug('Converting Excel to PDF using PowerShell', [
                'input_file' => $inputFile,
                'output_path' => $outputPath
            ]);
            
            if (!file_exists($inputFile)) {
                throw new \RuntimeException("Input file not found: $inputFile");
            }
            
            // Create PowerShell script in temp directory
            $psScriptPath = sys_get_temp_dir() . '/convert_excel_to_pdf_' . uniqid() . '.ps1';
            $psScript = <<<PS
# PowerShell script to convert Excel to PDF using Excel application
\$ErrorActionPreference = "Stop"
try {
    \$excel = New-Object -ComObject Excel.Application
    \$excel.Visible = \$false
    \$excel.DisplayAlerts = \$false
    
    \$workbook = \$excel.Workbooks.Open("$inputFile")
    
    # PDF format constant (0 = xlTypePDF)
    \$workbook.ExportAsFixedFormat(0, "$outputPath")
    
    \$workbook.Close(\$false)
    \$excel.Quit()
    
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject(\$workbook) | Out-Null
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject(\$excel) | Out-Null
    Remove-Variable excel, workbook
    
    Write-Output "Conversion successful"
    exit 0
} catch {
    Write-Error \$_.Exception.Message
    exit 1
}
PS;
            
            file_put_contents($psScriptPath, $psScript);
            
            // Execute PowerShell script
            $output = [];
            $returnCode = 0;
            $command = "powershell.exe -ExecutionPolicy Bypass -File \"$psScriptPath\" 2>&1";
            
            \Log::debug('Executing PowerShell command', ['command' => $command]);
            exec($command, $output, $returnCode);
            
            \Log::debug('PowerShell execution result', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output)
            ]);
            
            // Clean up script file
            @unlink($psScriptPath);
            
            // Check if the command was successful
            if ($returnCode !== 0) {
                throw new \RuntimeException("PowerShell conversion failed with return code $returnCode: " . implode("\n", $output));
            }
            
            // Check if the output file was created
            if (!file_exists($outputPath)) {
                throw new \RuntimeException("Output PDF file not created: $outputPath");
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error in PowerShell conversion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Special handling for files with Arabic or other non-English content
     * 
     * @param string $inputFile Path to Excel file
     * @param array $options Conversion options
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet|null
     */
    protected function loadExcelWithSpecialHandling(string $inputFile, array $options = []): ?\PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        try {
            // Detect file type by extension
            $fileType = IOFactory::identify($inputFile);
            $reader = IOFactory::createReader($fileType);
            
            // Set encoding options for proper character handling
            if (method_exists($reader, 'setInputEncoding')) {
                $reader->setInputEncoding('UTF-8');
            }
            
            // Only read data if that's what we're asked to do
            $reader->setReadDataOnly($options['read_data_only'] ?? false);
            
            // Load the file
            $spreadsheet = $reader->load($inputFile);
            
            // Apply special handling for non-English content
            if (isset($options['worksheet_option']) && $options['worksheet_option'] === 'active') {
                // Use only the active sheet
                $activeSheet = $spreadsheet->getActiveSheet();
                
                // Ensure proper right-to-left text direction for Arabic content
                if ($this->containsArabicText($activeSheet)) {
                    $activeSheet->setRightToLeft(true);
                }
            } else {
                // Process all sheets
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    // Ensure proper right-to-left text direction for Arabic content
                    if ($this->containsArabicText($sheet)) {
                        $sheet->setRightToLeft(true);
                    }
                }
            }
            
            return $spreadsheet;
        } catch (\Exception $e) {
            \Log::error('Error loading Excel file with special handling: ' . $e->getMessage(), [
                'file' => $inputFile,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Check if a worksheet contains Arabic text
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return bool
     */
    protected function containsArabicText(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): bool
    {
        // Get the used range of cells
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        
        // Check a sample of cells for Arabic characters
        $sampleSize = min(100, $highestRow); // Check up to 100 rows
        
        for ($row = 1; $row <= $sampleSize; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cellValue = $sheet->getCell($col . $row)->getValue();
                if (is_string($cellValue) && preg_match('/[\x{0600}-\x{06FF}]/u', $cellValue)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function convertExcelToPdf(string $inputFile, string $outputPath, array $options = []): bool
    {
        try {
            Log::debug('Starting Excel to PDF conversion', [
                'input_file' => $inputFile,
                'output_path' => $outputPath,
                'options' => $options
            ]);

            // Create output directory if needed
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Try different conversion methods in order of preference
            if ($this->convertWithMaatwebsite($inputFile, $outputPath, $options)) {
                return true;
            }

            if ($this->convertWithPhpSpreadsheet($inputFile, $outputPath, $options)) {
                return true;
            }

            Log::error('All Excel to PDF conversion methods failed');
            return false;
        } catch (\Exception $e) {
            Log::error('Error in Excel to PDF conversion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function convertWithMaatwebsite(string $inputFile, string $outputPath, array $options = []): bool
    {
        try {
            // Check if Maatwebsite Excel is available
            if (!class_exists('\Maatwebsite\Excel\Facades\Excel')) {
                Log::warning('Maatwebsite Excel library not available');
                return false;
            }

            Log::debug('Converting Excel to PDF using Maatwebsite Excel and DomPDF');

            // Load Excel file using Maatwebsite Excel
            $spreadsheet = \Maatwebsite\Excel\Facades\Excel::toArray(null, $inputFile)[0];

            // Generate HTML from Excel data
            $html = $this->generateHtmlFromExcel($spreadsheet, $options);

            // Configure DomPDF
            $dompdf = new \Dompdf\Dompdf([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans'
            ]);

            // Set paper size and orientation
            $dompdf->setPaper($options['page_size'] ?? 'A4', $options['orientation'] ?? 'portrait');

            // Load HTML into DomPDF
            $dompdf->loadHtml($html);

            // Render PDF
            $dompdf->render();

            // Save PDF
            file_put_contents($outputPath, $dompdf->output());

            return true;
        } catch (\Exception $e) {
            Log::error('Error in Maatwebsite Excel conversion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
} 