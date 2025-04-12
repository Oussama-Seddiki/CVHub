<?php

namespace App\Services\Pdf\Converters;

use App\Services\Pdf\PdfInterface;
use Symfony\Component\Process\Process;

class PdfToWordService implements PdfInterface
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
     * Create a new PDF to Word converter instance
     * 
     * @param string|null $libreOfficePath Path to LibreOffice executable
     */
    public function __construct(?string $libreOfficePath = null)
    {
        // Use provided path or try to auto-detect
        $this->libreOfficePath = $libreOfficePath ?? $this->detectLibreOfficePath();
    }
    
    /**
     * Process a PDF to Word conversion
     *
     * @param string $inputPath Path to input PDF file
     * @param string $outputPath Path to output DOCX file
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
            
            // Make sure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            // Get file extension from output path
            $extension = pathinfo($outputPath, PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'docx';
                $outputPath .= '.docx';
            }
            
            // Use appropriate format based on extension
            $format = match(strtolower($extension)) {
                'doc' => 'doc',
                'rtf' => 'rtf',
                'txt' => 'txt',
                default => 'docx',
            };
            
            // Get temp directory for conversion (LibreOffice works best with a directory)
            $tempDir = sys_get_temp_dir() . '/pdf_to_word_' . uniqid();
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Copy input file to temp directory
            $tempInputFile = $tempDir . '/' . basename($inputPath);
            copy($inputPath, $tempInputFile);
            
            // Use LibreOffice for the conversion
            if (!$this->libreOfficePath) {
                throw new \RuntimeException("LibreOffice/OpenOffice not found. Please install it or specify the path.");
            }
            
            // Build the command for conversion
            $command = [
                $this->libreOfficePath,
                '--headless',
                '--convert-to', 
                $format,
                '--outdir', 
                $tempDir,
                $tempInputFile
            ];
            
            // Execute the command
            $process = new Process($command);
            $process->setTimeout(60); // 1 minute timeout
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw new \RuntimeException("Conversion failed: " . $process->getErrorOutput());
            }
            
            // Find the converted file
            $convertedFileName = pathinfo($tempInputFile, PATHINFO_FILENAME) . '.' . $format;
            $convertedFilePath = $tempDir . '/' . $convertedFileName;
            
            if (!file_exists($convertedFilePath)) {
                throw new \RuntimeException("Conversion failed: Output file not found");
            }
            
            // Move to final destination
            rename($convertedFilePath, $outputPath);
            
            // Clean up temp directory
            $this->cleanupTempDir($tempDir);
            
            $this->info = [
                'success' => true,
                'message' => 'PDF converted to Word successfully',
                'details' => [
                    'input_file' => $inputPath,
                    'output_file' => $outputPath,
                    'output_format' => $format,
                    'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                ]
            ];
            
            return true;
        } catch (\Exception $e) {
            $this->info = [
                'success' => false,
                'message' => 'Failed to convert PDF to Word: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
            
            return false;
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
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($dir);
    }
} 