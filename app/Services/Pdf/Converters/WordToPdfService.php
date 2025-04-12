<?php

namespace App\Services\Pdf\Converters;

use App\Services\Pdf\PdfInterface;
use Symfony\Component\Process\Process;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class WordToPdfService implements PdfInterface
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
    
    /**
     * Create a new Word to PDF converter instance
     * 
     * @param string|null $libreOfficePath Path to LibreOffice executable
     */
    public function __construct(?string $libreOfficePath = null)
    {
        // Use provided path or try to auto-detect
        $this->libreOfficePath = $libreOfficePath ?? $this->detectLibreOfficePath();
    }
    
    /**
     * Process a Word to PDF conversion
     *
     * @param string $inputPath Path to input Word file
     * @param string $outputPath Path to output PDF file
     * @param array $options Processing options
     * @return bool Success or failure
     */
    public function process(string $inputPath, string $outputPath, array $options = []): bool
    {
        $this->info = [];
        
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
            
            \Log::debug('Word to PDF conversion starting', [
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
            
            // Ensure output file has PDF extension
            if (!preg_match('/\.pdf$/i', $outputPath)) {
                $outputPath .= '.pdf';
            }
            
            // Get temp directory for conversion
            $tempDir = sys_get_temp_dir() . '/word_to_pdf_' . uniqid();
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    throw new \RuntimeException("Failed to create temp directory: $tempDir");
                }
            }
            
            // Copy input file to temp directory with error checking
            $tempInputFile = $tempDir . '/' . basename($inputPath);
            if (!copy($inputPath, $tempInputFile)) {
                throw new \RuntimeException("Failed to copy input file to temp directory: $inputPath to $tempInputFile");
            }
            
            // Check if temp file was created successfully
            if (!file_exists($tempInputFile) || !is_readable($tempInputFile)) {
                throw new \RuntimeException("Failed to access temp file after copying: $tempInputFile");
            }
            
            // Log detailed environment information
            \Log::debug('Word to PDF conversion environment', [
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
            
            // Check if OCR processing is requested
            $useOcr = isset($options['use_ocr']) && $options['use_ocr'] === true;
            
            // If OCR processing is requested, use the enhanced OCR method
            if ($useOcr) {
                \Log::info('Using OCR enhanced processing for Word to PDF conversion');
                
                $ocrResult = $this->processWithOcr($tempInputFile, $outputPath, $options);
                
                if (!$ocrResult) {
                    \Log::warning('OCR processing failed, falling back to standard conversion');
                    $useOcr = false; // Fall back to standard conversion
                } else {
                    // OCR processing successful
                    $publicPath = $this->storeInPublicStorage($outputPath);
                    $this->cleanupTempDir($tempDir);
                    
                    $this->info = [
                        'success' => true,
                        'message' => 'Word document converted to PDF successfully with OCR enhancement',
                        'output_path' => $publicPath,
                        'details' => [
                            'input_file' => $inputPath,
                            'output_file' => $outputPath,
                            'public_path' => $publicPath,
                            'quality' => $options['quality'] ?? 'standard',
                            'conversion_method' => 'ocr_enhanced',
                            'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                            'ocr_language' => $options['ocr_language'] ?? 'eng+ara',
                            'ocr_dpi' => $options['ocr_dpi'] ?? 300
                        ]
                    ];
                    
                    return true;
                }
            }
            
            // If not using OCR or OCR processing failed, proceed with standard conversion methods
            if (!$useOcr) {
                // 1. First try: Use PowerShell conversion (on Windows)
                if (PHP_OS === 'WINNT' || PHP_OS === 'Windows' || PHP_OS === 'WIN32') {
                    \Log::info('Attempting to use PowerShell conversion for Word to PDF');
                    if ($this->convertWithPowerShell($tempInputFile, $outputPath)) {
                        $publicPath = $this->storeInPublicStorage($outputPath);
                        $this->cleanupTempDir($tempDir);
                        
                        $this->info = [
                            'success' => true,
                            'message' => 'Word document converted to PDF successfully using PowerShell',
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
                
                // 2. Second try: Use LibreOffice for the conversion
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
                    \Log::info('Using LibreOffice for Word to PDF conversion at: ' . $libreOfficePath);
                    
                    if ($this->convertWithLibreOffice($tempInputFile, $tempDir, $outputPath, $options)) {
                        $publicPath = $this->storeInPublicStorage($outputPath);
                        $this->cleanupTempDir($tempDir);
                        
                        $this->info = [
                            'success' => true,
                            'message' => 'Word document converted to PDF successfully using LibreOffice',
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
                
                // 3. Third try: COM automation (Windows only)
                if (PHP_OS === 'WINNT' || PHP_OS === 'Windows' || PHP_OS === 'WIN32') {
                    \Log::info('Trying Word to PDF conversion with COM automation');
                    
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
                            'message' => 'Word document converted to PDF successfully using COM automation',
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
                
                // 4. Fourth try: PHPWord library (fallback method)
                \Log::info('Trying Word to PDF conversion with PHPWord library');
                
                $phpWordResult = $this->convertWithPhpWord($tempInputFile, $tempDir);
                if ($phpWordResult && file_exists($phpWordResult)) {
                    if (!rename($phpWordResult, $outputPath)) {
                        // If rename fails, try copy+delete
                        if (copy($phpWordResult, $outputPath)) {
                            @unlink($phpWordResult);
                        } else {
                            throw new \RuntimeException("Failed to move converted file from $phpWordResult to $outputPath");
                        }
                    }
                    
                    $publicPath = $this->storeInPublicStorage($outputPath);
                    $this->cleanupTempDir($tempDir);
                    
                    $this->info = [
                        'success' => true,
                        'message' => 'Word document converted to PDF successfully using PHPWord',
                        'output_path' => $publicPath,
                        'details' => [
                            'input_file' => $inputPath,
                            'output_file' => $outputPath,
                            'public_path' => $publicPath,
                            'quality' => $options['quality'] ?? 'standard',
                            'conversion_method' => 'phpword',
                            'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                        ]
                    ];
                    
                    return true;
                } else {
                    \Log::warning('PHPWord conversion failed or not available');
                }
                
                // If all methods failed
                $errorMessage = "All conversion methods failed. Please install LibreOffice or ensure PHP has appropriate permissions.";
                \Log::error($errorMessage);
                throw new \RuntimeException($errorMessage);
            }
            
        } catch (\Exception $e) {
            \Log::error('Word to PDF conversion error: ' . $e->getMessage(), [
                'input_path' => $inputPath,
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->info = [
                'success' => false,
                'message' => 'Failed to convert Word document to PDF: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
            
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
        // Create a unique filename
        $filename = uniqid('word_to_pdf_') . '.pdf';
        
        // Public storage directory
        $publicDir = storage_path('app/public/pdf');
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        
        // Destination path
        $destPath = $publicDir . '/' . $filename;
        
        // Copy the file
        copy($pdfPath, $destPath);
        
        // Return the relative storage path
        return 'public/pdf/' . $filename;
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
     * Try to convert using COM automation on Windows
     *
     * @param string $inputFile Input Word file
     * @param string $outputDir Output directory
     * @return string|null Path to converted file or null on failure
     */
    protected function convertWithCOM(string $inputFile, string $outputDir): ?string
    {
        try {
            if (!class_exists('COM')) {
                return null;
            }
            
            \Log::debug('Attempting Word to PDF conversion using COM automation');
            
            $word = new \COM('Word.Application');
            $word->Visible = false;
            $word->DisplayAlerts = false;

            $doc = $word->Documents->Open($inputFile);
            
            $outputFile = $outputDir . '/' . pathinfo($inputFile, PATHINFO_FILENAME) . '.pdf';
            
            // PDF format constant (wdFormatPDF = 17)
            $doc->SaveAs($outputFile, 17);
            $doc->Close(false);
            $word->Quit();
            
            unset($doc);
            unset($word);
            
            return $outputFile;
        } catch (\Exception $e) {
            \Log::warning('COM automation failed: ' . $e->getMessage());
            return null;
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
     * Convert Word file to PDF using LibreOffice with enhanced settings
     *
     * @param string $inputFile Input Word file path
     * @param string $outputDir Output directory
     * @param string $outputPath Final output PDF path
     * @param array $options Conversion options
     * @return bool Success status
     */
    protected function convertWithLibreOffice(string $inputFile, string $outputDir, string $outputPath, array $options = []): bool
    {
        try {
            \Log::debug('Converting Word to PDF using LibreOffice (direct method)', [
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
            $batchContent .= '"' . $libreOfficePath . '" --headless --nofirststartwizard --norestore -env:UserInstallation=file:///C:/temp/LibreOffice_UserProfile --convert-to pdf --outdir "' . $outputDir . '" "' . $inputFile . '"' . PHP_EOL;
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
     * Convert using PHPWord library
     *
     * @param string $inputFile Input Word file
     * @param string $outputDir Output directory
     * @return string|null Path to converted file or null on failure
     */
    protected function convertWithPhpWord(string $inputFile, string $outputDir): ?string
    {
        try {
            // Check if PHPWord is available
            if (!class_exists('\PhpOffice\PhpWord\IOFactory')) {
                \Log::warning('PHPWord library not available');
                return null;
            }
            
            \Log::debug('Attempting Word to PDF conversion using PHPWord library');
            
            // Check if we have a PDF renderer available
            $pdfRenderer = Settings::PDF_RENDERER_DOMPDF;
            $pdfRendererPath = base_path('vendor/dompdf/dompdf');
            
            if (!file_exists($pdfRendererPath)) {
                \Log::warning('DomPDF renderer not found at ' . $pdfRendererPath);
                
                // Try TCPDF as an alternative
                $pdfRenderer = Settings::PDF_RENDERER_TCPDF;
                $pdfRendererPath = base_path('vendor/tecnickcom/tcpdf');
                
                if (!file_exists($pdfRendererPath)) {
                    \Log::warning('TCPDF renderer not found either - no PDF renderer available');
                    return null;
                }
            }
            
            // Set PDF renderer settings
            Settings::setPdfRendererName($pdfRenderer);
            Settings::setPdfRendererPath($pdfRendererPath);
            
            \Log::info('Using PDF renderer: ' . $pdfRenderer);
            
            // Make sure temp directory has correct permissions
            if (!is_writable($outputDir)) {
                chmod($outputDir, 0755);
                \Log::info('Updated permissions on temp directory: ' . $outputDir);
            }
            
            // Load the Word document
            $phpWord = IOFactory::load($inputFile);
            
            // Create output filename
            $outputFile = $outputDir . '/' . pathinfo($inputFile, PATHINFO_FILENAME) . '.pdf';
            
            // Save as PDF
            $xmlWriter = IOFactory::createWriter($phpWord, 'PDF');
            $xmlWriter->save($outputFile);
            
            if (file_exists($outputFile)) {
                \Log::info('PHPWord conversion successful: ' . $outputFile);
                return $outputFile;
            } else {
                \Log::warning('PHPWord conversion output file not found: ' . $outputFile);
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::warning('PHPWord conversion failed: ' . $e->getMessage());
            \Log::warning('Exception trace: ' . $e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Process Word to PDF conversion with enhanced OCR
     * This method will first convert the document using LibreOffice
     * then improve the result with OCR for better text recognition
     * 
     * @param string $inputPath Path to input Word file
     * @param string $outputPath Path to output PDF file
     * @param array $options Processing options
     * @return bool Success status
     */
    protected function processWithOcr(string $inputPath, string $outputPath, array $options = []): bool
    {
        try {
            // Create temporary directory for processing
            $tempDir = sys_get_temp_dir() . '/word_to_pdf_ocr_' . uniqid();
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    throw new \RuntimeException("Failed to create temporary directory: $tempDir");
                }
            }
            
            \Log::debug('Starting Word to PDF conversion with OCR enhancement', [
                'input_path' => $inputPath,
                'output_path' => $outputPath,
                'options' => $options,
                'temp_dir' => $tempDir
            ]);
            
            // Step 1: Convert Word to PDF using standard method
            $initialPdfPath = $tempDir . '/initial_conversion.pdf';
            $initialResult = $this->convertWithLibreOffice($inputPath, $tempDir, $initialPdfPath, $options);
            
            if (!$initialResult || !file_exists($initialPdfPath)) {
                \Log::error('Initial Word to PDF conversion failed during OCR process', [
                    'input_path' => $inputPath,
                    'initial_pdf_path' => $initialPdfPath,
                    'exists' => file_exists($initialPdfPath)
                ]);
                
                // Clean up and return false
                $this->cleanupTempDir($tempDir);
                return false;
            }
            
            \Log::debug('Initial Word to PDF conversion successful, proceeding to OCR enhancement', [
                'initial_pdf_path' => $initialPdfPath,
                'file_size' => filesize($initialPdfPath)
            ]);
            
            // Step 2: Prepare OCR options
            $ocrOptions = [
                'language' => $options['ocr_language'] ?? 'eng+ara', // Default to English and Arabic
                'dpi' => $options['ocr_dpi'] ?? 300,
                'enhance_tables' => $options['enhance_tables'] ?? true,
                'enhance_formatting' => $options['enhance_formatting'] ?? true,
                'preserve_original_look' => true
            ];
            
            // Step 3: Apply OCR processing for better text recognition
            // Get OCR service from the container
            $ocrService = app()->make('App\Services\Pdf\Ocr\OcrService');
            
            if (!$ocrService) {
                \Log::warning('OCR Service not available, using standard converted PDF', [
                    'output_path' => $outputPath
                ]);
                
                // Just move the initial PDF to the output path
                if (!rename($initialPdfPath, $outputPath)) {
                    copy($initialPdfPath, $outputPath);
                }
                
                $this->cleanupTempDir($tempDir);
                return file_exists($outputPath);
            }
            
            // Create a temporary path for the OCR processed file
            $ocrEnhancedPath = $tempDir . '/ocr_enhanced.pdf';
            
            \Log::debug('Starting OCR enhancement process', [
                'input' => $initialPdfPath,
                'output' => $ocrEnhancedPath,
                'ocr_options' => $ocrOptions
            ]);
            
            // Process the PDF with OCR
            $ocrResult = $ocrService->process($initialPdfPath, $ocrEnhancedPath, $ocrOptions);
            
            // Decide which file to use as the final output
            if ($ocrResult && file_exists($ocrEnhancedPath)) {
                \Log::info('OCR enhancement successful, using OCR-enhanced PDF', [
                    'ocr_path' => $ocrEnhancedPath,
                    'file_size' => filesize($ocrEnhancedPath)
                ]);
                
                // Move the OCR-enhanced file to the output path
                if (!rename($ocrEnhancedPath, $outputPath)) {
                    copy($ocrEnhancedPath, $outputPath);
                }
            } else {
                \Log::warning('OCR enhancement failed, using standard converted PDF', [
                    'initial_path' => $initialPdfPath
                ]);
                
                // Fallback to the initial converted PDF
                if (!rename($initialPdfPath, $outputPath)) {
                    copy($initialPdfPath, $outputPath);
                }
            }
            
            // Clean up
            $this->cleanupTempDir($tempDir);
            
            return file_exists($outputPath);
        } catch (\Exception $e) {
            \Log::error('Error in Word to PDF with OCR process', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Convert Word to PDF using PowerShell and Microsoft Word (Windows-only)
     *
     * @param string $inputFile Input Word file 
     * @param string $outputPath Output PDF file
     * @return bool Success or failure
     */
    protected function convertWithPowerShell(string $inputFile, string $outputPath): bool
    {
        try {
            \Log::debug('Converting Word to PDF using PowerShell', [
                'input_file' => $inputFile,
                'output_path' => $outputPath
            ]);
            
            if (!file_exists($inputFile)) {
                throw new \RuntimeException("Input file not found: $inputFile");
            }
            
            // Create PowerShell script in temp directory
            $psScriptPath = sys_get_temp_dir() . '/convert_word_to_pdf_' . uniqid() . '.ps1';
            $psScript = <<<PS
# PowerShell script to convert Word to PDF using Word application
\$ErrorActionPreference = "Stop"
try {
    \$word = New-Object -ComObject Word.Application
    \$word.Visible = \$false
    \$doc = \$word.Documents.Open("$inputFile")
    \$pdfFormat = 17 # wdFormatPDF
    \$doc.SaveAs("$outputPath", \$pdfFormat)
    \$doc.Close(\$false)
    \$word.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject(\$doc) | Out-Null
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject(\$word) | Out-Null
    Remove-Variable word, doc
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
} 