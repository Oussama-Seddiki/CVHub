<?php

namespace App\Services\Pdf;

use App\Services\Pdf\Converters\PdfToWordService;
use App\Services\Pdf\Converters\PdfToImageService;
use App\Services\Pdf\Converters\ImagesToPdfService;
use App\Services\Pdf\Editors\MergePdfService;
use App\Services\Pdf\Editors\ExtractPagesService;
use App\Services\Pdf\Editors\RemovePagesService;
use App\Services\Pdf\Ocr\OcrService;
use App\Services\Pdf\Security\PasswordProtectService;
use App\Services\Storage\TemporaryStorage;
use App\Services\Pdf\Converters\WordToPdfService;
use App\Services\Pdf\Converters\PptToPdfService;
use App\Services\Pdf\Converters\ExcelToPdfService;

class PdfServiceFactory
{
    /**
     * Paths to external binaries
     * 
     * @var array
     */
    protected $binaryPaths = [];
    
    /**
     * Storage service for temporary files
     * 
     * @var TemporaryStorage
     */
    protected $tempStorage;
    
    /**
     * Create a new PDF service factory
     * 
     * @param TemporaryStorage $tempStorage Temporary storage service
     * @param array $binaryPaths Paths to external binaries
     */
    public function __construct(TemporaryStorage $tempStorage, array $binaryPaths = [])
    {
        $this->tempStorage = $tempStorage;
        $this->binaryPaths = $binaryPaths;
    }
    
    /**
     * Set binary paths
     * 
     * @param array $paths Array of binary paths
     * @return self
     */
    public function setBinaryPaths(array $paths): self
    {
        $this->binaryPaths = array_merge($this->binaryPaths, $paths);
        return $this;
    }
    
    /**
     * Get a PDF merge service
     * 
     * @return MergePdfService
     */
    public function getMergePdfService(): MergePdfService
    {
        return new MergePdfService();
    }
    
    /**
     * Get a PDF to Word conversion service
     * 
     * @return PdfToWordService
     */
    public function getPdfToWordService(): PdfToWordService
    {
        return new PdfToWordService(
            $this->binaryPaths['libreoffice'] ?? null
        );
    }
    
    /**
     * Get a PDF page extraction service
     * 
     * @return ExtractPagesService
     */
    public function getExtractPagesService(): ExtractPagesService
    {
        return new ExtractPagesService();
    }
    
    /**
     * Get a PDF page removal service
     * 
     * @return RemovePagesService
     */
    public function getRemovePagesService(): RemovePagesService
    {
        return new RemovePagesService();
    }
    
    /**
     * Get an OCR service
     * 
     * @return OcrService
     */
    public function getOcrService(): OcrService
    {
        return new OcrService(
            $this->binaryPaths['tesseract'] ?? null,
            $this->binaryPaths['ghostscript'] ?? null
        );
    }
    
    /**
     * Get a password protection service
     * 
     * @return PasswordProtectService
     */
    public function getPasswordProtectService(): PasswordProtectService
    {
        return new PasswordProtectService(
            $this->binaryPaths['qpdf'] ?? null
        );
    }
    
    /**
     * Get a PDF to Image conversion service
     * 
     * @return PdfToImageService
     */
    public function getPdfToImageService(): PdfToImageService
    {
        return new PdfToImageService();
    }
    
    /**
     * Get an Images to PDF conversion service
     * 
     * @return ImagesToPdfService
     */
    public function getImagesToPdfService(): ImagesToPdfService
    {
        return new ImagesToPdfService();
    }
    
    /**
     * Get a Word to PDF conversion service
     * 
     * @return WordToPdfService
     */
    public function getWordToPdfService(): WordToPdfService
    {
        return new WordToPdfService(
            $this->binaryPaths['libreoffice'] ?? null
        );
    }
    
    /**
     * Get a PowerPoint to PDF conversion service
     * 
     * @return PptToPdfService
     */
    public function getPptToPdfService(): PptToPdfService
    {
        return new PptToPdfService();
    }
    
    /**
     * Get a Excel to PDF conversion service
     * 
     * @return ExcelToPdfService
     */
    public function getExcelToPdfService(): ExcelToPdfService
    {
        return new ExcelToPdfService(
            $this->binaryPaths['libreoffice'] ?? null
        );
    }
    
    /**
     * Get a particular service
     *
     * @param string $serviceType Type of service to get
     * @return PdfInterface Service instance
     * @throws \InvalidArgumentException If service type is not supported
     */
    public function getService(string $serviceType): PdfInterface
    {
        return match($serviceType) {
            'images_to_pdf' => app(ImagesToPdfService::class),
            'merge_pdf' => app(MergePdfService::class),
            'split_pdf' => app(SplitPdfService::class),
            'word_to_pdf' => app(WordToPdfService::class),
            'ppt_to_pdf' => app(PptToPdfService::class),
            'excel_to_pdf' => app(ExcelToPdfService::class),
            'ocr' => app(OcrService::class),
            default => throw new \InvalidArgumentException("Unsupported service type: $serviceType")
        };
    }
    
    /**
     * Process a document or set of documents
     *
     * @param string $serviceType Type of processing to perform
     * @param mixed $input Input data (could be file path or array of file paths)
     * @param array $options Processing options
     * @return array Result information
     */
    public function process(string $serviceType, $input, array $options = []): array
    {
        try {
            $service = $this->getService($serviceType);
            
            $outputFile = $this->getOutputFilePath($serviceType, $input, $options);
            
            if ($serviceType === 'images_to_pdf') {
                // For images to PDF, $input is an array of image paths
                if (!is_array($input)) {
                    $input = [$input];
                }
                
                $result = $service->process($input, $outputFile, $options);
            } elseif ($serviceType === 'merge_pdf') {
                // For merge PDF, $input is an array of PDF paths
                if (!is_array($input)) {
                    $input = [$input];
                }
                
                $result = $service->process($input, $outputFile, $options);
            } elseif (in_array($serviceType, ['word_to_pdf', 'ppt_to_pdf', 'excel_to_pdf'])) {
                // For conversions, $input is a single file path
                $result = $service->process($input, $outputFile, $options);
            } else {
                // For other services (like split PDF), $input is a single file path
                $result = $service->process($input, $outputFile, $options);
            }
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Processing failed: ' . ($service->getInfo()['message'] ?? 'Unknown error'),
                    'details' => $service->getInfo()
                ];
            }
            
            return [
                'success' => true,
                'message' => $service->getInfo()['message'] ?? 'Processing completed successfully',
                'output_path' => $service->getInfo()['output_path'] ?? $outputFile,
                'details' => $service->getInfo()
            ];
        } catch (\Exception $e) {
            \Log::error('Error processing document: ' . $e->getMessage(), [
                'service_type' => $serviceType,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage(),
                'error_type' => get_class($e)
            ];
        }
    }
    
    /**
     * Get output file path based on service type and input
     *
     * @param string $serviceType Type of service
     * @param mixed $input Input file or files
     * @param array $options Processing options
     * @return string Output file path
     */
    protected function getOutputFilePath(string $serviceType, $input, array $options = []): string
    {
        // Output directory
        $outputDir = storage_path('app/temp');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Generate a unique filename
        $uniqueId = uniqid();
        
        return match($serviceType) {
            'images_to_pdf' => $outputDir . '/images_to_pdf_' . $uniqueId . '.pdf',
            'merge_pdf' => $outputDir . '/merged_pdf_' . $uniqueId . '.pdf',
            'split_pdf' => $outputDir . '/split_pdf_' . $uniqueId,  // For split PDF, this is a directory
            'word_to_pdf' => $outputDir . '/word_to_pdf_' . $uniqueId . '.pdf',
            'ppt_to_pdf' => $outputDir . '/ppt_to_pdf_' . $uniqueId . '.pdf',  // Add output path for PowerPoint to PDF
            'excel_to_pdf' => $outputDir . '/excel_to_pdf_' . $uniqueId . '.pdf',
            default => $outputDir . '/output_' . $uniqueId . '.pdf'
        };
    }
    
    /**
     * Detect paths to required binaries
     * 
     * @return array Array of binary paths
     */
    public static function detectBinaryPaths(): array
    {
        $binaryPaths = [];
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        \Log::debug('Detecting binary paths', ['is_windows' => $isWindows]);
        
        // Detect LibreOffice
        if ($isWindows) {
            // Common Windows paths for LibreOffice
            $possibleLibreOfficePaths = [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            ];
            
            foreach ($possibleLibreOfficePaths as $path) {
                if (file_exists($path)) {
                    $binaryPaths['libreoffice'] = $path;
                    break;
                }
            }
            
            // Try using 'where' command if not found
            if (!isset($binaryPaths['libreoffice']) && function_exists('exec')) {
                exec('where soffice 2>NUL', $libreOfficeOutput, $libreOfficeExitCode);
                if ($libreOfficeExitCode === 0 && !empty($libreOfficeOutput[0])) {
                    $binaryPaths['libreoffice'] = $libreOfficeOutput[0];
                }
            }
        } else {
            // Unix/Linux detection
            exec("which libreoffice 2>/dev/null", $libreOfficeOutput, $libreOfficeExitCode);
            if ($libreOfficeExitCode === 0 && !empty($libreOfficeOutput[0])) {
                $binaryPaths['libreoffice'] = $libreOfficeOutput[0];
            } else {
                // Try soffice if libreoffice not found
                exec("which soffice 2>/dev/null", $sofficeOutput, $sofficeExitCode);
                if ($sofficeExitCode === 0 && !empty($sofficeOutput[0])) {
                    $binaryPaths['libreoffice'] = $sofficeOutput[0];
                }
            }
        }
        
        // Detect Tesseract
        if ($isWindows) {
            $possibleTesseractPaths = [
                'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
                'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            ];
            
            foreach ($possibleTesseractPaths as $path) {
                if (file_exists($path)) {
                    $binaryPaths['tesseract'] = $path;
                    break;
                }
            }
            
            // Try using 'where' command if not found
            if (!isset($binaryPaths['tesseract']) && function_exists('exec')) {
                exec('where tesseract 2>NUL', $tesseractOutput, $tesseractExitCode);
                if ($tesseractExitCode === 0 && !empty($tesseractOutput[0])) {
                    $binaryPaths['tesseract'] = $tesseractOutput[0];
                }
            }
        } else {
            exec("which tesseract 2>/dev/null", $tesseractOutput, $tesseractExitCode);
            if ($tesseractExitCode === 0 && !empty($tesseractOutput[0])) {
                $binaryPaths['tesseract'] = $tesseractOutput[0];
            }
        }
        
        // Detect GhostScript
        if ($isWindows) {
            // Check for multiple GS versions on Windows
            $gsVersions = [];
            $gsBaseDirs = [
                'C:\\Program Files\\gs\\',
                'C:\\Program Files (x86)\\gs\\'
            ];
            
            foreach ($gsBaseDirs as $baseDir) {
                if (is_dir($baseDir)) {
                    $versionDirs = glob($baseDir . 'gs*', GLOB_ONLYDIR);
                    foreach ($versionDirs as $versionDir) {
                        $gsExecutable = $versionDir . '\\bin\\gswin64c.exe';
                        $gsExecutable32 = $versionDir . '\\bin\\gswin32c.exe';
                        
                        if (file_exists($gsExecutable)) {
                            $gsVersions[] = $gsExecutable;
                        } elseif (file_exists($gsExecutable32)) {
                            $gsVersions[] = $gsExecutable32;
                        }
                    }
                }
            }
            
            // Sort versions descending and use the latest
            if (!empty($gsVersions)) {
                rsort($gsVersions);
                $binaryPaths['ghostscript'] = $gsVersions[0];
            }
            
            // Try using 'where' command if not found
            if (!isset($binaryPaths['ghostscript']) && function_exists('exec')) {
                // Try gswin64c first
                exec('where gswin64c 2>NUL', $gsOutput, $gsExitCode);
                if ($gsExitCode === 0 && !empty($gsOutput[0])) {
                    $binaryPaths['ghostscript'] = $gsOutput[0];
                } else {
                    // Try gswin32c as fallback
                    exec('where gswin32c 2>NUL', $gsOutput, $gsExitCode);
                    if ($gsExitCode === 0 && !empty($gsOutput[0])) {
                        $binaryPaths['ghostscript'] = $gsOutput[0];
                    }
                }
            }
        } else {
            exec("which gs 2>/dev/null", $gsOutput, $gsExitCode);
            if ($gsExitCode === 0 && !empty($gsOutput[0])) {
                $binaryPaths['ghostscript'] = $gsOutput[0];
            }
        }
        
        // Detect QPDF
        if ($isWindows) {
            $possibleQpdfPaths = [
                'C:\\Program Files\\qpdf\\bin\\qpdf.exe',
                'C:\\Program Files (x86)\\qpdf\\bin\\qpdf.exe',
            ];
            
            foreach ($possibleQpdfPaths as $path) {
                if (file_exists($path)) {
                    $binaryPaths['qpdf'] = $path;
                    break;
                }
            }
            
            // Try using 'where' command if not found
            if (!isset($binaryPaths['qpdf']) && function_exists('exec')) {
                exec('where qpdf 2>NUL', $qpdfOutput, $qpdfExitCode);
                if ($qpdfExitCode === 0 && !empty($qpdfOutput[0])) {
                    $binaryPaths['qpdf'] = $qpdfOutput[0];
                }
            }
        } else {
            exec("which qpdf 2>/dev/null", $qpdfOutput, $qpdfExitCode);
            if ($qpdfExitCode === 0 && !empty($qpdfOutput[0])) {
                $binaryPaths['qpdf'] = $qpdfOutput[0];
            }
        }
        
        \Log::info('Binary paths detected', $binaryPaths);
        return $binaryPaths;
    }
    
    /**
     * Get the binary paths for external tools
     * 
     * @return array Array of binary paths
     */
    public function getBinaryPaths(): array
    {
        return $this->binaryPaths;
    }
} 