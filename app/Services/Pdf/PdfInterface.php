<?php

namespace App\Services\Pdf;

interface PdfInterface
{
    /**
     * Process a PDF file
     *
     * @param string $inputPath Path to input file
     * @param string $outputPath Path to output file
     * @param array $options Processing options
     * @return bool Success or failure
     */
    public function process(string $inputPath, string $outputPath, array $options = []): bool;
    
    /**
     * Get information about the operation
     *
     * @return array Information about current operation
     */
    public function getInfo(): array;
} 