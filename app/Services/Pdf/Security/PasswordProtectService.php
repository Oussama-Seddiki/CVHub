<?php

namespace App\Services\Pdf\Security;

use App\Services\Pdf\PdfInterface;
use Symfony\Component\Process\Process;

class PasswordProtectService implements PdfInterface
{
    /**
     * Information about the operation
     * 
     * @var array
     */
    protected $info = [];
    
    /**
     * Path to QPDF executable
     */
    protected $qpdfPath;
    
    /**
     * Create a new password protection service instance
     * 
     * @param string|null $qpdfPath Path to QPDF executable
     */
    public function __construct(?string $qpdfPath = null)
    {
        $this->qpdfPath = $qpdfPath ?? $this->detectQpdfPath();
    }
    
    /**
     * Process a PDF file to add password protection
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
            
            // Make sure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            // Check for required QPDF executable
            if (!$this->qpdfPath) {
                throw new \RuntimeException("QPDF not found. Please install it or specify the path.");
            }
            
            // Get options
            $userPassword = $options['user_password'] ?? null;
            $ownerPassword = $options['owner_password'] ?? null;
            $keyLength = $options['key_length'] ?? 128;
            $restrictions = $options['restrictions'] ?? [];
            
            // Validate passwords
            if (empty($userPassword) && empty($ownerPassword)) {
                throw new \InvalidArgumentException("At least one password (user or owner) must be provided");
            }
            
            // Build the command
            $command = [$this->qpdfPath];
            
            // Add password options
            if (!empty($userPassword)) {
                $command[] = '--encrypt';
                $command[] = $userPassword;
                $command[] = $ownerPassword ?? $userPassword;
                $command[] = $keyLength;
                
                // Add restrictions if any
                if (!empty($restrictions)) {
                    if (in_array('no-print', $restrictions)) {
                        $command[] = '--print=none';
                    }
                    if (in_array('no-copy', $restrictions)) {
                        $command[] = '--modify=none';
                        $command[] = '--extract=n';
                    }
                    if (in_array('no-modify', $restrictions)) {
                        $command[] = '--modify=none';
                    }
                }
                
                $command[] = '--';
            }
            
            // Add input and output files
            $command[] = $inputPath;
            $command[] = $outputPath;
            
            // Execute the command
            $process = new Process($command);
            $process->setTimeout(60); // 1 minute timeout
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw new \RuntimeException("Failed to add password protection: " . $process->getErrorOutput());
            }
            
            $this->info = [
                'success' => true,
                'message' => 'Password protection added successfully',
                'details' => [
                    'input_file' => $inputPath,
                    'output_file' => $outputPath,
                    'has_user_password' => !empty($userPassword),
                    'has_owner_password' => !empty($ownerPassword),
                    'key_length' => $keyLength,
                    'restrictions' => $restrictions,
                    'output_size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                ]
            ];
            
            return true;
        } catch (\Exception $e) {
            $this->info = [
                'success' => false,
                'message' => 'Failed to add password protection: ' . $e->getMessage(),
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
     * Detect QPDF path
     *
     * @return string|null The path if found, null otherwise
     */
    protected function detectQpdfPath(): ?string
    {
        // Common paths on different operating systems
        $possiblePaths = [
            // Windows
            'C:\\Program Files\\QPDF\\bin\\qpdf.exe',
            'C:\\Program Files (x86)\\QPDF\\bin\\qpdf.exe',
            
            // macOS and Linux
            '/usr/bin/qpdf',
            '/usr/local/bin/qpdf',
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
            exec('which qpdf 2>/dev/null', $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output[0])) {
                return $output[0];
            }
        }
        
        return null;
    }
} 