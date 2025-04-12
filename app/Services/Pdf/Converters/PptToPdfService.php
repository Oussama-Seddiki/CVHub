<?php

namespace App\Services\Pdf\Converters;

use App\Services\Pdf\PdfInterface;
use Symfony\Component\Process\Process;

class PptToPdfService implements PdfInterface
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
     * Path to Java executable
     */
    protected $javaPath;
    
    /**
     * Create a new PowerPoint to PDF converter instance
     * 
     * @param string|null $libreOfficePath Path to LibreOffice executable
     * @param string|null $javaPath Path to Java executable
     */
    public function __construct(?string $libreOfficePath = null, ?string $javaPath = null)
    {
        // Use provided path or try to auto-detect
        $this->libreOfficePath = $libreOfficePath ?? $this->detectLibreOfficePath();
        $this->javaPath = $javaPath ?? $this->detectJavaPath();
    }
    
    /**
     * Process a PowerPoint to PDF conversion
     *
     * @param string $inputPath Path to input PowerPoint file
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
            
            // Check file extension
            $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
            if (!in_array($extension, ['ppt', 'pptx', 'odp'])) {
                throw new \InvalidArgumentException("Invalid file type: $extension. Only PPT, PPTX, and ODP files are supported");
            }
            
            // Process options with validation
            $quality = isset($options['quality']) ? strtolower($options['quality']) : 'standard';
            $includeNotes = isset($options['include_notes']) ? (bool)$options['include_notes'] : false;
            
            // Validate quality setting
            if (!in_array($quality, ['standard', 'high', 'very_high'])) {
                $quality = 'standard';
            }
            
            \Log::debug('PowerPoint to PDF conversion starting', [
                'input_path' => $inputPath,
                'output_path' => $outputPath,
                'file_size' => $fileSize,
                'file_exists' => file_exists($inputPath),
                'file_readable' => is_readable($inputPath),
                'options' => [
                    'quality' => $quality,
                    'include_notes' => $includeNotes
                ]
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
            $tempDir = sys_get_temp_dir() . '/ppt_to_pdf_' . uniqid();
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
            
            // Build LibreOffice command with options
            $result = $this->convertWithLibreOffice($tempInputFile, $outputPath, $quality, $includeNotes);
            
            if ($result) {
                $this->info = [
                    'success' => true,
                    'message' => 'PowerPoint converted to PDF successfully',
                    'output_path' => $outputPath,
                    'details' => [
                        'quality' => $quality,
                        'include_notes' => $includeNotes
                    ]
                ];
                return true;
            } else {
                $this->info = [
                    'success' => false,
                    'message' => 'Failed to convert PowerPoint to PDF',
                    'details' => [
                        'quality' => $quality,
                        'include_notes' => $includeNotes,
                        'error' => 'LibreOffice conversion failed'
                    ]
                ];
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('PowerPoint to PDF conversion failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->info = [
                'success' => false,
                'message' => 'Failed to convert PowerPoint to PDF: ' . $e->getMessage()
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
        $filename = uniqid('ppt_to_pdf_') . '.pdf';
        
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
    protected function detectLibreOfficePath(): ?string
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
        if (!is_dir($dir)) {
            return;
        }
        
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
        
        rmdir($dir);
    }
    
    /**
     * Try to convert using COM automation on Windows
     *
     * @param string $inputFile Input PowerPoint file
     * @param string $outputDir Output directory
     * @return string|null Path to converted file or null on failure
     */
    protected function convertWithCOM(string $inputFile, string $outputDir): ?string
    {
        try {
            if (!class_exists('COM')) {
                return null;
            }
            
            \Log::debug('Attempting PowerPoint to PDF conversion using COM automation');
            
            $powerpoint = new \COM('PowerPoint.Application');
            $powerpoint->Visible = false;
            $powerpoint->DisplayAlerts = false;

            $presentation = $powerpoint->Presentations->Open($inputFile, false, false, false);
            
            $outputFile = $outputDir . '/' . pathinfo($inputFile, PATHINFO_FILENAME) . '.pdf';
            
            // Export as PDF (ppSaveAsPDF = 32)
            $presentation->SaveAs($outputFile, 32);
            $presentation->Close();
            $powerpoint->Quit();
            
            unset($presentation);
            unset($powerpoint);
            
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
    protected function findLibreOfficeExecutable(): ?string
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
     * Convert PowerPoint to PDF using LibreOffice
     * 
     * @param string $inputFile Input PowerPoint file path
     * @param string $outputPath Output PDF file path
     * @param string $quality Quality setting (standard, high, very_high)
     * @param bool $includeNotes Whether to include presenter notes
     * @return bool Success or failure
     */
    protected function convertWithLibreOffice(string $inputFile, string $outputPath, string $quality, bool $includeNotes): bool
    {
        $tempDir = dirname($inputFile);
        
        // Map quality settings to PDF export options
        $qualityOptions = [
            'standard' => 'ScreenOptimized', // Default for standard quality
            'high' => 'PdfPrintingQuality',  // Higher quality, larger file
            'very_high' => 'PdfPrintingQuality:300' // Highest quality with 300 DPI
        ];
        
        $pdfQualityOption = $qualityOptions[$quality] ?? $qualityOptions['standard'];
        
        // LibreOffice's impress_pdf_Export filter options
        // Format: FilterName:FilterOption1=Value1,FilterOption2=Value2
        $filterOptions = '';
        if ($includeNotes) {
            // Latest version filter option for notes
            $filterOptions = 'impress_pdf_Export:ExportNotesPages=true,UseLosslessCompression=true';
            
            // Add quality setting
            if ($quality === 'very_high') {
                $filterOptions .= ',Quality=100';
            } elseif ($quality === 'high') {
                $filterOptions .= ',Quality=90';
            } else {
                $filterOptions .= ',Quality=75';
            }
        } else {
            // Standard PDF export without notes
            $filterOptions = 'writer_pdf_Export:UseLosslessCompression=true';
            
            // Add quality setting
            if ($quality === 'very_high') {
                $filterOptions .= ',Quality=100';
            } elseif ($quality === 'high') {
                $filterOptions .= ',Quality=90';
            } else {
                $filterOptions .= ',Quality=75';
            }
        }
        
        \Log::debug('LibreOffice conversion options:', [
            'quality' => $quality, 
            'quality_option' => $pdfQualityOption,
            'include_notes' => $includeNotes,
            'filter_options' => $filterOptions,
            'java_available' => $this->javaPath !== null
        ]);
        
        try {
            // Set JAVA_HOME or JRE_HOME if Java path is available
            $env = [];
            if ($this->javaPath !== null) {
                $javaDir = dirname($this->javaPath);
                $javaHome = dirname($javaDir); // Go up one level from bin directory
                
                $env['JAVA_HOME'] = $javaHome;
                $env['JRE_HOME'] = $javaHome;
                $env['PATH'] = $javaDir . PATH_SEPARATOR . getenv('PATH');
                
                \Log::debug('Setting Java environment variables', [
                    'JAVA_HOME' => $javaHome,
                    'JRE_HOME' => $javaHome,
                    'java_bin_dir' => $javaDir
                ]);
            }
            
            // Build LibreOffice command with options
            $command = [
                $this->libreOfficePath,
                '--headless',
                // Fix the file URI format for Windows - this is the critical part
                '-env:UserInstallation=file:///C:/temp/LibreOffice_UserProfile_' . uniqid(),
                '--convert-to', 
                'pdf:' . $filterOptions,
                '--outdir', 
                $tempDir,
                $inputFile
            ];
            
            // Log the command for debugging purposes
            \Log::debug('LibreOffice conversion command: ' . implode(' ', $command));
            
            // Set proper environment variables for Windows
            if (PHP_OS === 'WINNT' || PHP_OS === 'Windows' || PHP_OS === 'WIN32') {
                $env = array_merge($env, [
                    'HOME' => 'C:\\temp',
                    'TEMP' => 'C:\\temp',
                    'TMP' => 'C:\\temp',
                    'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
                    'windir' => getenv('windir') ?: 'C:\\Windows',
                ]);
                
                // Add APPDATA and other critical Windows environment variables
                foreach (['APPDATA', 'ProgramData', 'LOCALAPPDATA', 'USERPROFILE'] as $envVar) {
                    if (getenv($envVar)) {
                        $env[$envVar] = getenv($envVar);
                    }
                }
            }
            
            \Log::debug('Setting environment variables for LibreOffice conversion', $env);
            
            // Execute the command with longer timeout (10 minutes)
            $process = new Process($command, null, $env);
            $process->setTimeout(600); // 10 minute timeout
            $process->run();
            
            // Log the output for debugging
            \Log::debug('LibreOffice output: ' . $process->getOutput());
            if (!empty($process->getErrorOutput())) {
                \Log::warning('LibreOffice error output: ' . $process->getErrorOutput());
            }
            
            // Get the output filename (LibreOffice names it based on the input filename)
            $outputFileName = pathinfo($inputFile, PATHINFO_FILENAME) . '.pdf';
            $tempOutputPath = $tempDir . '/' . $outputFileName;
            
            // Check if the output file was created
            if (file_exists($tempOutputPath)) {
                // Move the file to the desired output path
                if (!rename($tempOutputPath, $outputPath)) {
                    // If rename fails, try copy and delete
                    if (copy($tempOutputPath, $outputPath)) {
                        unlink($tempOutputPath);
                    } else {
                        throw new \RuntimeException("Failed to move the converted PDF file from $tempOutputPath to $outputPath");
                    }
                }
                
                return true;
            } else {
                \Log::error('LibreOffice did not generate the expected output file', [
                    'expected_output' => $tempOutputPath,
                    'command_exit_code' => $process->getExitCode(),
                    'process_output' => $process->getOutput(),
                    'process_error' => $process->getErrorOutput()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('Error in LibreOffice conversion:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Set the path to the LibreOffice executable
     *
     * @param string $path Path to LibreOffice executable
     * @return self
     */
    public function setLibreOfficePath(string $path): self
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("LibreOffice executable not found at: $path");
        }
        
        $this->libreOfficePath = $path;
        \Log::info('LibreOffice path set to: ' . $path);
        
        return $this;
    }

    /**
     * Detect Java path
     *
     * @return string|null The path if found, null otherwise
     */
    protected function detectJavaPath(): ?string
    {
        // Common paths on different operating systems
        $commonJavaPaths = [
            // Windows
            'C:\\Program Files\\Java\\jre*\\bin\\java.exe',
            'C:\\Program Files\\Java\\jdk*\\bin\\java.exe',
            'C:\\Program Files (x86)\\Java\\jre*\\bin\\java.exe',
            'C:\\Program Files (x86)\\Java\\jdk*\\bin\\java.exe',
            'C:\\ProgramData\\Oracle\\Java\\javapath\\java.exe',
            
            // macOS
            '/Library/Java/JavaVirtualMachines/*/Contents/Home/bin/java',
            '/usr/bin/java',
            
            // Linux
            '/usr/bin/java',
            '/usr/lib/jvm/*/bin/java'
        ];
        
        foreach ($commonJavaPaths as $pattern) {
            $matches = glob($pattern);
            if (!empty($matches)) {
                $javaPath = $matches[0];
                \Log::info('Found Java at: ' . $javaPath);
                return $javaPath;
            }
        }
        
        // Try to find using which command on Unix systems
        if (function_exists('exec')) {
            $output = [];
            $returnVar = 0;
            exec('which java 2>/dev/null', $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output[0])) {
                \Log::info('Found Java using which command: ' . $output[0]);
                return $output[0];
            }
        }
        
        \Log::info('Java path not detected');
        return null;
    }

    /**
     * Set the path to Java
     *
     * @param string $path Path to Java executable
     * @return self
     */
    public function setJavaPath(string $path): self
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Java executable not found at: $path");
        }
        
        $this->javaPath = $path;
        \Log::info('Java path set to: ' . $path);
        
        return $this;
    }
} 