<?php

namespace App\Services\Pdf\Ocr;

use App\Services\Pdf\PdfInterface;
use Symfony\Component\Process\Process;

class OcrService implements PdfInterface
{
    /**
     * Information about the operation
     * 
     * @var array
     */
    protected $info = [];
    
    /**
     * Path to Ghostscript executable
     */
    protected $ghostscriptPath;
    
    /**
     * Path to Tesseract executable
     */
    protected $tesseractPath;
    
    /**
     * Create a new OCR service instance
     * 
     * @param string|null $tesseractPath Path to Tesseract executable
     * @param string|null $ghostscriptPath Path to Ghostscript executable
     */
    public function __construct(?string $tesseractPath = null, ?string $ghostscriptPath = null)
    {
        // Try to detect Tesseract
        $this->tesseractPath = $tesseractPath ?? $this->detectTesseractPath();
        
        // Try to detect Ghostscript
        $this->ghostscriptPath = $ghostscriptPath ?? $this->detectGhostscriptPath();
        
        // Log detailed information for debugging
        \Log::info('OCR Service initialized', [
            'tesseract_path' => $this->tesseractPath,
            'ghostscript_path' => $this->ghostscriptPath,
            'tesseract_exists' => $this->tesseractPath ? file_exists($this->tesseractPath) : false,
            'ghostscript_exists' => $this->ghostscriptPath ? file_exists($this->ghostscriptPath) : false,
            'gs_version' => $this->getGhostscriptVersion(),
            'tesseract_version' => $this->getTesseractVersion()
        ]);
    }
    
    /**
     * Process a PDF file with OCR
     *
     * @param string $inputPath Path to input PDF file
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
            
            \Log::debug('OCR Process starting', [
                'input_path' => $inputPath,
                'output_path' => $outputPath,
                'options' => $options
            ]);
            
            // Make sure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            // Get options
            $language = $options['language'] ?? 'eng';
            $dpi = $options['dpi'] ?? 300;
            $textOnly = $options['text_only'] ?? false;
            
            // Create temp directory
            $tempDir = sys_get_temp_dir() . '/ocr_' . uniqid();
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Verify tools are available
            if (!$this->ghostscriptPath || !file_exists($this->ghostscriptPath)) {
                throw new \RuntimeException("Ghostscript not found or not accessible at: " . ($this->ghostscriptPath ?? 'unknown path'));
            }
            
            if (!$this->tesseractPath || !file_exists($this->tesseractPath)) {
                throw new \RuntimeException("Tesseract OCR not found or not accessible at: " . ($this->tesseractPath ?? 'unknown path'));
            }
            
            // If we need the text only output
            if ($textOnly) {
                return $this->extractTextFromPdf($inputPath, $outputPath, $language, $dpi, $tempDir);
            } else {
                // We need to OCR the PDF and create a searchable PDF
                return $this->createSearchablePdf($inputPath, $outputPath, $language, $dpi, $tempDir);
            }
        } catch (\Exception $e) {
            \Log::error('OCR processing failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->info = [
                'success' => false,
                'message' => 'Failed to perform OCR: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
            
            return false;
        }
    }
    
    /**
     * Extract text from PDF using OCR
     *
     * @param string $inputPath Path to input PDF file
     * @param string $outputPath Path to output text file
     * @param string $language OCR language
     * @param int $dpi DPI for image extraction
     * @param string $tempDir Temporary directory
     * @return bool Success or failure
     */
    protected function extractTextFromPdf(string $inputPath, string $outputPath, string $language, int $dpi, string $tempDir): bool
    {
        \Log::debug('Extracting text from PDF', [
            'input_path' => $inputPath,
            'output_path' => $outputPath,
            'language' => $language,
            'dpi' => $dpi
        ]);
        
        // Extract images from PDF using Ghostscript
        $imageFiles = $this->extractImagesFromPdf($inputPath, $tempDir, $dpi);
        
        if (empty($imageFiles)) {
            throw new \RuntimeException("Failed to extract images from PDF");
        }
        
        // Process each image with Tesseract
        $allText = '';
        
        // Sort the files by page number
        usort($imageFiles, function ($a, $b) {
            $pageA = (int) preg_replace('/^.*page-(\d+)\.png$/', '$1', $a);
            $pageB = (int) preg_replace('/^.*page-(\d+)\.png$/', '$1', $b);
            return $pageA - $pageB;
        });
        
        // Process each image
        foreach ($imageFiles as $index => $imageFile) {
            \Log::debug('Processing image with Tesseract', [
                'image_file' => $imageFile,
                'page' => $index + 1
            ]);
            
            // Run Tesseract directly through command-line for better control
            $outputBase = $tempDir . '/page-' . ($index + 1);
            $txtOutput = $outputBase . '.txt';
            
            $command = [
                $this->tesseractPath,
                $imageFile,
                $outputBase,
                '-l', $language,
                '--psm', '1', // Automatic page segmentation with OSD
                '--dpi', (string)$dpi,
                'txt' // Output format
            ];
            
            $process = new Process($command);
            $process->setTimeout(120); // 2 minutes timeout per page
            $process->run();
            
            if (!$process->isSuccessful()) {
                \Log::warning('Tesseract OCR process failed', [
                    'command' => implode(' ', $command),
                    'error' => $process->getErrorOutput(),
                    'image_file' => $imageFile
                ]);
                continue; // Skip this page but continue with others
            }
            
            if (file_exists($txtOutput)) {
                $pageText = file_get_contents($txtOutput);
                
                // Add page separator
                if ($index > 0) {
                    $allText .= "\n\n--- Page " . ($index + 1) . " ---\n\n";
                }
                
                $allText .= $pageText;
            } else {
                \Log::warning('Tesseract output not found', [
                    'expected_output' => $txtOutput
                ]);
            }
        }
        
        // Write text to the output file
        file_put_contents($outputPath, $allText);
        
        // Clean up temp files
        $this->cleanupTempDir($tempDir);
        
        $this->info = [
            'success' => true,
            'message' => 'OCR text extraction completed successfully',
            'details' => [
                'input_file' => $inputPath,
                'output_file' => $outputPath,
                'language' => $language,
                'pages_processed' => count($imageFiles),
                'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
            ]
        ];
        
        return true;
    }
    
    /**
     * Create a searchable PDF with OCR
     *
     * @param string $inputPath Path to input PDF file
     * @param string $outputPath Path to output PDF file
     * @param string $language OCR language
     * @param int $dpi DPI for image extraction
     * @param string $tempDir Temporary directory
     * @return bool Success or failure
     */
    protected function createSearchablePdf(string $inputPath, string $outputPath, string $language, int $dpi, string $tempDir): bool
    {
        \Log::debug('Creating searchable PDF', [
            'input_path' => $inputPath,
            'output_path' => $outputPath,
            'language' => $language,
            'dpi' => $dpi
        ]);
        
        // Extract images from PDF
        $imageFiles = $this->extractImagesFromPdf($inputPath, $tempDir, $dpi);
        
        if (empty($imageFiles)) {
            throw new \RuntimeException("Failed to extract images from PDF");
        }
        
        // Sort the files by page number
        usort($imageFiles, function ($a, $b) {
            $pageA = (int) preg_replace('/^.*page-(\d+)\.png$/', '$1', $a);
            $pageB = (int) preg_replace('/^.*page-(\d+)\.png$/', '$1', $b);
            return $pageA - $pageB;
        });
        
        // Create a PDF for each page with text overlay
        $pdfFiles = [];
        foreach ($imageFiles as $index => $imageFile) {
            \Log::debug('Processing page for searchable PDF', [
                'image_file' => $imageFile,
                'page' => $index + 1
            ]);
            
            // Run Tesseract with PDF output
            $outputBase = $tempDir . '/page-' . ($index + 1);
            $pdfFile = $outputBase . '.pdf';
            
            // Use Tesseract to create a searchable PDF
            $command = [
                $this->tesseractPath,
                $imageFile,
                $outputBase,
                '-l', $language,
                '--psm', '1', // Automatic page segmentation with OSD
                '--dpi', (string)$dpi,
                'pdf' // Output in PDF format with text overlay
            ];
            
            $process = new Process($command);
            $process->setTimeout(120); // 2 minutes timeout per page
            $process->run();
            
            if (!$process->isSuccessful()) {
                \Log::warning('Tesseract PDF creation failed', [
                    'command' => implode(' ', $command),
                    'error' => $process->getErrorOutput()
                ]);
                continue; // Skip this page but continue with others
            }
            
            if (file_exists($pdfFile)) {
                $pdfFiles[] = $pdfFile;
            } else {
                \Log::warning('Tesseract PDF output not found', [
                    'expected_output' => $pdfFile
                ]);
            }
        }
        
        // Now merge all the PDFs using Ghostscript
        if (!empty($pdfFiles)) {
            \Log::debug('Merging OCR PDFs', [
                'pdf_files' => $pdfFiles,
                'output_path' => $outputPath
            ]);
            
            $this->mergePdfs($pdfFiles, $outputPath);
        } else {
            // If we couldn't create OCR PDFs, just copy the original as fallback
            \Log::warning('No OCR PDFs created, using original as fallback', [
                'input_path' => $inputPath,
                'output_path' => $outputPath
            ]);
            
            copy($inputPath, $outputPath);
        }
        
        // Clean up temp files
        $this->cleanupTempDir($tempDir);
        
        $this->info = [
            'success' => true,
            'message' => 'Searchable PDF created successfully',
            'details' => [
                'input_file' => $inputPath,
                'output_file' => $outputPath,
                'language' => $language,
                'pages_processed' => count($pdfFiles),
                'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
            ]
        ];
        
        return true;
    }
    
    /**
     * Merge multiple PDFs into a single PDF using Ghostscript
     *
     * @param array $pdfFiles Array of PDF file paths
     * @param string $outputPath Output PDF path
     * @return void
     */
    protected function mergePdfs(array $pdfFiles, string $outputPath): void
    {
        if (empty($pdfFiles)) {
            throw new \InvalidArgumentException("No PDF files to merge");
        }
        
        $pdfListFile = tempnam(sys_get_temp_dir(), 'pdf_list_');
        file_put_contents($pdfListFile, implode("\n", $pdfFiles));
        
        // Merge PDFs using Ghostscript
        $command = [
            $this->ghostscriptPath,
            '-dSAFER',
            '-dBATCH',
            '-dNOPAUSE',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS=/prepress',
            '-sOutputFile=' . $outputPath,
            '@' . $pdfListFile
        ];
        
        $process = new Process($command);
        $process->setTimeout(300); // 5 minutes timeout
        $process->run();
        
        // Clean up the list file
        if (file_exists($pdfListFile)) {
            unlink($pdfListFile);
        }
        
        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Failed to merge PDFs: " . $process->getErrorOutput());
        }
    }
    
    /**
     * Extract images from PDF using Ghostscript
     *
     * @param string $pdfPath Path to PDF file
     * @param string $outputDir Directory for extracted images
     * @param int $dpi DPI for image extraction
     * @return array Array of extracted image file paths
     */
    protected function extractImagesFromPdf(string $pdfPath, string $outputDir, int $dpi): array
    {
        \Log::debug('Extracting images from PDF', [
            'pdf_path' => $pdfPath,
            'output_dir' => $outputDir,
            'dpi' => $dpi
        ]);
        
        // Fix Windows path format for output dir
        $outputPattern = str_replace('\\', '/', $outputDir) . '/page-%d.png';
        
        // Command to extract images from PDF
        $command = [
            $this->ghostscriptPath,
            '-dSAFER',
            '-dBATCH',
            '-dNOPAUSE',
            '-sDEVICE=pngalpha',
            "-r{$dpi}",
            '-dTextAlphaBits=4',
            '-dGraphicsAlphaBits=4',
            "-sOutputFile={$outputPattern}",
            $pdfPath
        ];
        
        \Log::debug('Ghostscript extraction command', [
            'command' => implode(' ', $command)
        ]);
        
        $process = new Process($command);
        $process->setTimeout(300); // 5 minutes timeout
        $process->run();
        
        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput();
            \Log::error('Ghostscript extraction failed', [
                'error' => $error,
                'command' => implode(' ', $command)
            ]);
            throw new \RuntimeException("Failed to extract images from PDF: " . $error);
        }
        
        // Get the list of extracted images
        $imageFiles = glob($outputDir . '/page-*.png');
        
        \Log::debug('Extracted images from PDF', [
            'image_count' => count($imageFiles),
            'first_image' => !empty($imageFiles) ? $imageFiles[0] : null
        ]);
        
        return $imageFiles;
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
     * Detect Tesseract path
     *
     * @return string|null The path if found, null otherwise
     */
    public function detectTesseractPath(): ?string
    {
        // Common paths on different operating systems
        $possiblePaths = [
            // Windows
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            
            // macOS and Linux
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
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
            exec('which tesseract 2>/dev/null', $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output[0])) {
                return $output[0];
            }
            
            // Try 'where' on Windows
            exec('where tesseract 2>NUL', $output, $returnVar);
            if ($returnVar === 0 && !empty($output[0])) {
                return $output[0];
            }
        }
        
        \Log::warning('Tesseract OCR not found in common paths');
        return null;
    }
    
    /**
     * Detect Ghostscript path
     *
     * @return string|null The path if found, null otherwise
     */
    protected function detectGhostscriptPath(): ?string
    {
        // Common paths on different operating systems
        $possiblePaths = [
            // User's specific Ghostscript installation (exact path)
            'C:\\Program Files\\gs\\gs10.05.0\\bin\\gswin64c.exe',
            
            // Windows - check multiple versions
            'C:\\Program Files\\gs\\gs*\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\gs*\\bin\\gswin32c.exe',
            'C:\\Program Files\\gs\\gs*\\bin\\gswin64.exe',
            'C:\\Program Files (x86)\\gs\\gs*\\bin\\gswin32.exe',
            
            // Direct path variants for more recent Ghostscript versions
            'C:\\Program Files\\gs\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\bin\\gswin32c.exe',
            
            // Default installation paths for newer versions
            'C:\\Program Files\\gs\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\gswin32c.exe',
            
            // Standard Ghostscript installation paths
            'C:\\Program Files\\Ghostscript\\*\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\Ghostscript\\*\\bin\\gswin32c.exe',
            
            // Specific versions on Windows
            'C:\\Program Files\\gs\\gs10.0.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs9.55.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs9.54.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs9.53.3\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\gs10.0.0\\bin\\gswin32c.exe',
            'C:\\Program Files (x86)\\gs\\gs9.55.0\\bin\\gswin32c.exe',
            'C:\\Program Files (x86)\\gs\\gs9.54.0\\bin\\gswin32c.exe',
            'C:\\Program Files (x86)\\gs\\gs9.53.3\\bin\\gswin32c.exe',
            
            // macOS and Linux
            '/usr/bin/gs',
            '/usr/local/bin/gs',
        ];
        
        \Log::debug('Searching for Ghostscript in common paths', ['paths' => $possiblePaths]);
        
        // First try exact paths
        foreach ($possiblePaths as $path) {
            if (strpos($path, '*') === false && file_exists($path)) {
                \Log::info('Ghostscript found at: ' . $path);
                return $path;
            }
        }
        
        // Then try wildcard paths on Windows
        foreach ($possiblePaths as $path) {
            if (strpos($path, '*') !== false) {
                $matches = glob($path);
                if (!empty($matches)) {
                    \Log::info('Ghostscript found with wildcard at: ' . $matches[0]);
                    return $matches[0];
                }
            }
        }
        
        // Try to find using which command on Unix systems
        if (function_exists('exec')) {
            \Log::debug('Trying to locate Ghostscript using exec commands');
            
            $output = [];
            $returnVar = 0;
            exec('which gs 2>/dev/null', $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output[0])) {
                \Log::info('Ghostscript found using which command: ' . $output[0]);
                return $output[0];
            }
            
            // Try 'where' on Windows
            exec('where gswin64c 2>NUL', $output, $returnVar);
            if ($returnVar === 0 && !empty($output[0])) {
                \Log::info('Ghostscript found using where command: ' . $output[0]);
                return $output[0];
            }
            
            exec('where gswin32c 2>NUL', $output, $returnVar);
            if ($returnVar === 0 && !empty($output[0])) {
                \Log::info('Ghostscript found using where command: ' . $output[0]);
                return $output[0];
            }
            
            // Try 'where gs' on Windows
            exec('where gs 2>NUL', $output, $returnVar);
            if ($returnVar === 0 && !empty($output[0])) {
                \Log::info('Ghostscript (gs) found using where command: ' . $output[0]);
                return $output[0];
            }
        }
        
        \Log::warning('Ghostscript not found in common paths');
        return null;
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
            try {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to clean up temp file', [
                    'file' => $file->getRealPath(),
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        try {
            rmdir($dir);
        } catch (\Exception $e) {
            \Log::warning('Failed to remove temp directory', [
                'dir' => $dir,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get Ghostscript version
     * 
     * @return string Version or error message
     */
    protected function getGhostscriptVersion(): string
    {
        if (!$this->ghostscriptPath || !file_exists($this->ghostscriptPath)) {
            return 'Not installed or not found';
        }
        
        try {
            $command = ['"' . $this->ghostscriptPath . '"', '--version'];
            $process = new Process($command);
            $process->run();
            
            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
            
            return 'Error: ' . trim($process->getErrorOutput());
        } catch (\Exception $e) {
            return 'Exception: ' . $e->getMessage();
        }
    }
    
    /**
     * Get Tesseract version
     * 
     * @return string Version or error message
     */
    protected function getTesseractVersion(): string
    {
        if (!$this->tesseractPath || !file_exists($this->tesseractPath)) {
            return 'Not installed or not found';
        }
        
        try {
            $command = ['"' . $this->tesseractPath . '"', '--version'];
            $process = new Process($command);
            $process->run();
            
            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
            
            return 'Error: ' . trim($process->getErrorOutput());
        } catch (\Exception $e) {
            return 'Exception: ' . $e->getMessage();
        }
    }
} 