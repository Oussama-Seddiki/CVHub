<?php

namespace App\Http\Controllers;

use App\Services\Pdf\PdfServiceFactory;
use App\Services\Storage\TemporaryStorage;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Validator;
use App\Services\Pdf\Converters\PptToPdfService;

class PdfProcessingController extends Controller
{
    /**
     * PDF service factory
     * 
     * @var PdfServiceFactory
     */
    protected $pdfServiceFactory;
    
    /**
     * Temporary storage service
     * 
     * @var TemporaryStorage
     */
    protected $tempStorage;
    
    /**
     * Create a new controller instance
     * 
     * @param TemporaryStorage $tempStorage
     */
    public function __construct(TemporaryStorage $tempStorage)
    {
        $this->tempStorage = $tempStorage;
        
        // Detect binary paths
        $binaryPaths = PdfServiceFactory::detectBinaryPaths();
        
        // Log detected binaries for debugging
        \Log::info('PDF Processing Controller initialized with binaries', [
            'ghostscript' => $binaryPaths['ghostscript'] ?? 'not found',
            'tesseract' => $binaryPaths['tesseract'] ?? 'not found',
            'libreoffice' => $binaryPaths['libreoffice'] ?? 'not found',
            'qpdf' => $binaryPaths['qpdf'] ?? 'not found'
        ]);
        
        // Create the PDF service factory
        $this->pdfServiceFactory = new PdfServiceFactory($tempStorage, $binaryPaths);
    }
    
    /**
     * Process a PDF merge operation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mergePdf(Request $request)
    {
        // Validate the request
        $request->validate([
            'files' => 'required|array|min:2',
            'files.*' => 'required|file|mimes:pdf',
            'metadata.title' => 'nullable|string|max:255',
            'metadata.author' => 'nullable|string|max:255',
            'metadata.subject' => 'nullable|string|max:255',
            'metadata.keywords' => 'nullable|string|max:255',
        ]);
        
        try {
            // Store uploaded files
            $filePaths = [];
            foreach ($request->file('files') as $file) {
                $filePaths[] = $this->tempStorage->storeUploadedFile($file);
            }
            
            // Set up options
            $options = [
                'files' => $filePaths,
                'metadata' => $request->input('metadata', []),
            ];
            
            // Process the merge operation
            $result = $this->pdfServiceFactory->process('merge_pdf', $filePaths[0], $options);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to merge PDF files',
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'PDF files merged successfully',
                'data' => $result['details'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to merge PDF files: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Process a PDF to Word conversion
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pdfToWord(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|file|mimes:pdf',
            'format' => 'nullable|string|in:docx,doc,rtf,txt',
        ]);
        
        try {
            // Store uploaded file
            $filePath = $this->tempStorage->storeUploadedFile($request->file('file'));
            
            // Set up options
            $options = [
                'format' => $request->input('format', 'docx'),
            ];
            
            // Process the conversion
            $result = $this->pdfServiceFactory->process('pdf_to_word', $filePath, $options);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to convert PDF to Word',
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'PDF converted to Word successfully',
                'data' => $result['details'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to convert PDF to Word: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Process a PDF OCR operation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ocrPdf(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|file|mimes:pdf',
            'language' => 'nullable|string|max:10',
            'dpi' => 'nullable|integer|min:72|max:600',
            'text_only' => 'nullable|boolean',
        ]);
        
        try {
            // Store uploaded file
            $filePath = $this->tempStorage->storeUploadedFile($request->file('file'));
            
            // Set up options
            $options = [
                'language' => $request->input('language', 'eng'),
                'dpi' => $request->input('dpi', 300),
                'text_only' => $request->input('text_only', false),
            ];
            
            // Process the OCR
            $result = $this->pdfServiceFactory->process('ocr', $filePath, $options);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to process OCR',
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'OCR processed successfully',
                'data' => $result['details'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process OCR: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Process a PDF password protection operation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function passwordProtectPdf(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|file|mimes:pdf',
            'user_password' => 'nullable|string|max:255',
            'owner_password' => 'nullable|string|max:255',
            'key_length' => 'nullable|integer|in:40,128,256',
            'restrictions' => 'nullable|array',
            'restrictions.*' => 'string|in:no-print,no-copy,no-modify',
        ]);
        
        // Ensure at least one password is provided
        if (empty($request->input('user_password')) && empty($request->input('owner_password'))) {
            return response()->json([
                'success' => false,
                'message' => 'At least one password (user or owner) must be provided',
            ], 422);
        }
        
        try {
            // Store uploaded file
            $filePath = $this->tempStorage->storeUploadedFile($request->file('file'));
            
            // Set up options
            $options = [
                'user_password' => $request->input('user_password'),
                'owner_password' => $request->input('owner_password'),
                'key_length' => $request->input('key_length', 128),
                'restrictions' => $request->input('restrictions', []),
            ];
            
            // Process the password protection
            $result = $this->pdfServiceFactory->process('password_protect', $filePath, $options);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to add password protection',
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Password protection added successfully',
                'data' => $result['details'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add password protection: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Process a PDF page extraction operation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function extractPages(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|file|mimes:pdf',
            'pages' => 'required|string',
            'metadata.title' => 'nullable|string|max:255',
            'metadata.author' => 'nullable|string|max:255',
            'metadata.subject' => 'nullable|string|max:255',
            'metadata.keywords' => 'nullable|string|max:255',
        ]);
        
        try {
            // Store uploaded file
            $filePath = $this->tempStorage->storeUploadedFile($request->file('file'));
            
            // Set up options
            $options = [
                'pages' => $request->input('pages'),
                'metadata' => $request->input('metadata', []),
            ];
            
            // Process the extraction
            $result = $this->pdfServiceFactory->process('extract_pages', $filePath, $options);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to extract PDF pages',
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'PDF pages extracted successfully',
                'data' => $result['details'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extract PDF pages: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Show available PDF processing tools
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function tools()
    {
        // Detect binary paths
        $binaryPaths = PdfServiceFactory::detectBinaryPaths();
        
        $tools = [
            [
                'id' => 'merge_pdf',
                'name' => 'Merge PDF',
                'description' => 'Combine multiple PDF files into one',
                'available' => true, // Always available as it uses PHP libraries
                'endpoint' => route('pdf.merge'),
                'method' => 'POST',
                'requirements' => ['files' => 'array of PDF files (min 2)'],
            ],
            [
                'id' => 'extract_pages',
                'name' => 'Extract Pages',
                'description' => 'Extract specific pages from a PDF file',
                'available' => true, // Always available as it uses PHP libraries
                'endpoint' => route('pdf.extract_pages'),
                'method' => 'POST',
                'requirements' => ['file' => 'PDF file', 'pages' => 'Page ranges (e.g., "1,3-5,7")'],
            ],
            [
                'id' => 'remove_pages',
                'name' => 'Remove Pages',
                'description' => 'Remove specific pages from a PDF file',
                'available' => true, // Always available as it uses PHP libraries
                'endpoint' => route('pdf.remove_pages'),
                'method' => 'POST',
                'requirements' => ['file' => 'PDF file', 'pages' => 'Page ranges to remove (e.g., "1,3-5,7")'],
            ],
            [
                'id' => 'pdf_to_word',
                'name' => 'PDF to Word',
                'description' => 'Convert PDF files to Word documents',
                'available' => isset($binaryPaths['libreoffice']),
                'endpoint' => route('pdf.to_word'),
                'method' => 'POST',
                'requirements' => ['file' => 'PDF file'],
            ],
            [
                'id' => 'ocr',
                'name' => 'OCR PDF',
                'description' => 'Extract text from scanned PDF documents',
                'available' => isset($binaryPaths['tesseract']) && isset($binaryPaths['ghostscript']),
                'endpoint' => route('pdf.ocr'),
                'method' => 'POST',
                'requirements' => ['file' => 'PDF file'],
            ],
            [
                'id' => 'password_protect',
                'name' => 'Password Protect PDF',
                'description' => 'Add password protection to PDF files',
                'available' => isset($binaryPaths['qpdf']),
                'endpoint' => route('pdf.password_protect'),
                'method' => 'POST',
                'requirements' => ['file' => 'PDF file', 'user_password' => 'string'],
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'tools' => $tools,
                'binary_paths' => $binaryPaths,
            ],
        ]);
    }
    
    /**
     * Convert PDF to images
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pdfToImage(Request $request)
    {
        // Log request information for debugging
        \Log::debug('PDF to Image Request', [
            'has_file' => $request->hasFile('file'),
            'all_data' => $request->all(),
            'files' => $request->allFiles(),
        ]);
        
        try {
            // Validate the request
            $validator = \Validator::make($request->all(), [
                'file' => 'required|file|mimes:pdf|max:20480', // 20MB max
                'format' => 'nullable|string|in:jpg,png',
                'quality' => 'nullable|string|in:low,medium,high',
                'dpi' => 'nullable|integer|min:72|max:600',
                'pages' => 'nullable|string',
                'create_zip' => 'nullable|boolean',
            ]);
            
            if ($validator->fails()) {
                \Log::warning('PDF to Image validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }
            
            // Store uploaded file
            $filePath = $this->tempStorage->storeUploadedFile($request->file('file'));
            
            // Set up options
            $options = [
                'format' => $request->input('format', 'jpg'),
                'quality' => $request->input('quality', 'medium'),
                'dpi' => $request->input('dpi', 150),
                'pages' => $request->input('pages', 'all'),
                'create_zip' => $request->has('create_zip') ? (bool)$request->input('create_zip') : true,
            ];
            
            \Log::debug('Processing PDF to image with options', $options);
            \Log::debug('Received format parameter from request', [
                'format' => $request->input('format'),
                'raw_format' => $request->format,
                'format_in_options' => $options['format']
            ]);
            
            // Process the conversion
            $result = $this->pdfServiceFactory->process('pdf_to_image', $filePath, $options);
            
            if (!$result['success']) {
                \Log::error('PDF to image conversion failed', $result);
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to convert PDF to images',
                ], 500);
            }
            
            \Log::info('PDF to image conversion successful', [
                'details' => $result['details'] ?? []
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF converted to images successfully',
                'file' => $result['details']['url'] ?? null,
                'data' => $result['details'] ?? [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception in PDF to image conversion', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to convert PDF to images: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Process a PDF page removal operation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removePages(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|file|mimes:pdf',
            'pages' => 'required|string',
            'metadata.title' => 'nullable|string|max:255',
            'metadata.author' => 'nullable|string|max:255',
            'metadata.subject' => 'nullable|string|max:255',
            'metadata.keywords' => 'nullable|string|max:255',
        ]);
        
        try {
            // Store uploaded file
            $filePath = $this->tempStorage->storeUploadedFile($request->file('file'));
            
            // Log the operation for debugging
            \Log::debug('PDF Remove Pages Operation', [
                'file_path' => $filePath,
                'pages' => $request->input('pages'),
                'metadata' => $request->input('metadata', []),
            ]);
            
            // Set up options
            $options = [
                'pages' => $request->input('pages'),
                'metadata' => $request->input('metadata', []),
            ];
            
            // Process the page removal
            $result = $this->pdfServiceFactory->process('remove_pages', $filePath, $options);
            
            if (!$result['success']) {
                \Log::error('PDF page removal failed', $result);
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to remove PDF pages',
                    'debug_info' => config('app.debug') ? $result : null
                ], 500);
            }
            
            \Log::info('PDF pages removed successfully', [
                'details' => $result['details'] ?? []
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF pages removed successfully',
                'data' => $result['details'] ?? [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception in PDF page removal', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove PDF pages: ' . $e->getMessage(),
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null
            ], 500);
        }
    }
    
    /**
     * Generate previews for a PDF file
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ]);
        
        try {
            // Store uploaded file
            $filePath = $this->tempStorage->storeUploadedFile($request->file('file'));
            
            // Create PDF thumbnails
            $result = $this->pdfServiceFactory->createThumbnails($filePath);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to generate PDF preview',
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'PDF preview generated successfully',
                'pageCount' => $result['pageCount'] ?? 0,
                'thumbnails' => $result['thumbnails'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF preview: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Process a PDF file to organize its pages (reorder, rotate, remove)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function organizePdf(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|file|mimes:pdf',
            'pages_data' => 'required|string', // JSON string with page organization information
            'metadata.title' => 'nullable|string|max:255',
            'metadata.author' => 'nullable|string|max:255',
            'metadata.subject' => 'nullable|string|max:255',
            'metadata.keywords' => 'nullable|string|max:255',
        ]);
        
        try {
            // Store uploaded file
            $filePath = $this->tempStorage->storeUploadedFile($request->file('file'));
            
            // Parse the pages data
            $pagesData = json_decode($request->input('pages_data'), true);
            if (!is_array($pagesData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid pages data format',
                ], 400);
            }
            
            // Set up options
            $options = [
                'pages_data' => $pagesData,
                'metadata' => json_decode($request->input('metadata', '{}'), true),
            ];
            
            // Process the page organization
            $result = $this->pdfServiceFactory->process('organize_pdf', $filePath, $options);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to organize PDF pages',
                    'debug_info' => config('app.debug') ? $result : null
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'PDF pages organized successfully',
                'data' => $result['details'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to organize PDF pages: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Process images to PDF conversion
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function imagesToPdf(Request $request)
    {
        // Validate the request
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'required|file|mimes:jpeg,jpg,png,gif,webp,bmp',
            'page_size' => 'nullable|string|in:A4,A3,Letter,Legal',
            'orientation' => 'nullable|string|in:portrait,landscape',
            'margin' => 'nullable|integer|min:0|max:50',
        ]);
        
        try {
            // Store uploaded files
            $filePaths = [];
            foreach ($request->file('images') as $file) {
                $filePaths[] = $this->tempStorage->storeUploadedFile($file);
            }
            
            // Generate a custom output path with PDF extension
            $outputPath = $this->tempStorage->getTemporaryFilePath('pdf');
            
            // Set up options
            $options = [
                'files' => $filePaths,
                'page_size' => $request->input('page_size', 'A4'),
                'orientation' => $request->input('orientation', 'portrait'),
                'margin' => $request->input('margin', 10),
                'output_format' => 'pdf',
                'output_path' => $outputPath,
            ];
            
            // Log the operation for debugging
            \Log::debug('Images to PDF Operation', [
                'files' => count($filePaths),
                'options' => $options,
                'output_path' => $outputPath
            ]);
            
            // Process the image to PDF conversion
            $result = $this->pdfServiceFactory->process('images_to_pdf', $filePaths[0], $options);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to convert images to PDF',
                ], 500);
            }
            
            // Get the correct output file URL
            $outputPath = $result['output_path'] ?? '';
            $filename = basename($outputPath);
            
            // Generate a direct PDF serving URL instead of using storage
            $outputUrl = route('serve.pdf', ['filename' => $filename]);
            
            // Also generate a storage URL as backup
            $storageUrl = null;
            if (strpos($outputPath, 'public/') === 0) {
                // Remove 'public/' prefix for URL generation
                $relativePath = substr($outputPath, 7);
                $storageUrl = url('storage/' . $relativePath);
            } else {
                // Use direct storage URL
                $storageUrl = url('storage/' . $outputPath);
            }
            
            // Verify file exists
            $storagePath = storage_path('app/' . $outputPath);
            $fileExists = file_exists($storagePath);
            
            \Log::debug('Images to PDF Success', [
                'output_path' => $outputPath,
                'storage_path' => $storagePath,
                'file_exists' => $fileExists,
                'output_url' => $outputUrl,
                'storage_url' => $storageUrl,
                'output_size' => $fileExists ? filesize($storagePath) : 0,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Images converted to PDF successfully',
                'file' => $outputUrl,
                'filename' => basename($outputPath),
                'details' => array_merge($result['details'] ?? [], [
                    'file_size' => $fileExists ? filesize($storagePath) : 0,
                    'conversion_method' => $result['conversion_method'] ?? 'unknown'
                ]),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to convert images to PDF: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to convert images to PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Convert Word to PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function wordToPdf(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:doc,docx|max:20480', // 20MB max
                'quality' => 'sometimes|in:standard,high,very_high',
                'preserve_formatting' => 'sometimes|boolean',
                'orientation' => 'sometimes|in:default,portrait,landscape',
                'page_size' => 'sometimes|in:default,a4,letter,legal,a3,a5',
                'margins' => 'sometimes|in:default,narrow,normal,wide',
                'optimize_for_printing' => 'sometimes|boolean',
                'use_ocr' => 'sometimes|boolean',
                'ocr_language' => 'sometimes|string|max:10',
                'ocr_dpi' => 'sometimes|integer|min:150|max:600',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input parameters',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }
            
            // Check if file exists and is valid
            if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                \Log::error('Word to PDF conversion - Invalid file upload', [
                    'has_file' => $request->hasFile('file'),
                    'is_valid' => $request->hasFile('file') ? $request->file('file')->isValid() : false,
                    'error' => $request->file('file')->getError() ?? 'Unknown error'
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file upload. Please try again.',
                ], 400);
            }
            
            // Store uploaded file
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $originalExtension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();
            
            // Verify file extension is doc or docx
            if (!in_array(strtolower($originalExtension), ['doc', 'docx'])) {
                \Log::warning('Word to PDF conversion - Invalid file extension', [
                    'extension' => $originalExtension,
                    'mime_type' => $mimeType,
                    'original_name' => $originalName
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Only DOC and DOCX files are supported',
                ], 400);
            }
            
            // Copy the uploaded file to a more permanent location rather than using the temp file directly
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Create a unique filename to avoid collisions
            $tempFilename = uniqid('word_') . '.' . $originalExtension;
            $tempFilePath = $tempDir . '/' . $tempFilename;
            
            // Copy the uploaded file to our temp location
            if (!copy($file->getRealPath(), $tempFilePath)) {
                \Log::error('Word to PDF conversion - Failed to copy uploaded file', [
                    'source' => $file->getRealPath(),
                    'destination' => $tempFilePath,
                    'temp_file_exists' => file_exists($file->getRealPath()),
                    'temp_file_readable' => is_readable($file->getRealPath()),
                    'temp_dir_writable' => is_writable($tempDir)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process uploaded file. Please try again.',
                ], 500);
            }
            
            // Verify the file was copied successfully
            if (!file_exists($tempFilePath) || !is_readable($tempFilePath)) {
                \Log::error('Word to PDF conversion - Copied file not accessible', [
                    'file_path' => $tempFilePath,
                    'file_exists' => file_exists($tempFilePath),
                    'file_readable' => is_readable($tempFilePath),
                    'file_size' => file_exists($tempFilePath) ? filesize($tempFilePath) : 0
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process uploaded file. Please try again.',
                ], 500);
            }
            
            \Log::debug('Word file copied for conversion', [
                'original_name' => $originalName,
                'stored_path' => $tempFilePath,
                'file_size' => file_exists($tempFilePath) ? filesize($tempFilePath) : 0,
                'file_type' => $mimeType
            ]);
            
            // Set up options with enhanced parameters
            $options = [
                'quality' => $request->input('quality', 'standard'),
                'preserve_formatting' => (bool)$request->input('preserve_formatting', true),
                'original_filename' => $originalName,
            ];
            
            // Add additional options if provided
            if ($request->has('orientation') && $request->input('orientation') !== 'default') {
                $options['orientation'] = $request->input('orientation');
            }
            
            if ($request->has('page_size') && $request->input('page_size') !== 'default') {
                $options['page_size'] = $request->input('page_size');
            }
            
            if ($request->has('margins') && $request->input('margins') !== 'default') {
                $options['margins'] = $request->input('margins');
            }
            
            if ($request->has('optimize_for_printing')) {
                $options['optimize_for_printing'] = (bool)$request->input('optimize_for_printing');
            }
            
            // Add OCR options if requested
            if ($request->has('use_ocr')) {
                $options['use_ocr'] = (bool)$request->input('use_ocr');
                
                // Add OCR language if provided, default to English and Arabic for better multi-language support
                if ($request->has('ocr_language')) {
                    $options['ocr_language'] = $request->input('ocr_language');
                } else {
                    $options['ocr_language'] = 'eng+ara'; // Default to English and Arabic
                }
                
                // Add OCR DPI if provided, default to 300
                if ($request->has('ocr_dpi')) {
                    $options['ocr_dpi'] = (int)$request->input('ocr_dpi');
                } else {
                    $options['ocr_dpi'] = 300; // Default to 300 DPI
                }
                
                // Set OCR to enhance tables by default
                $options['enhance_tables'] = true;
                $options['enhance_formatting'] = true;
            }
            
            \Log::debug('Processing Word to PDF with options', $options);
            
            // Increase PHP execution time for OCR processing if needed
            if (isset($options['use_ocr']) && $options['use_ocr']) {
                set_time_limit(600); // 10 minutes for OCR processing
            } else {
                set_time_limit(300); // 5 minutes for standard conversion
            }
            
            // Process the conversion
            $result = $this->pdfServiceFactory->process('word_to_pdf', $tempFilePath, $options);
            
            if (!$result['success']) {
                \Log::error('Word to PDF conversion failed', array_merge($result, [
                    'input_file' => $tempFilePath,
                    'input_file_exists' => file_exists($tempFilePath),
                    'input_file_size' => file_exists($tempFilePath) ? filesize($tempFilePath) : 0
                ]));
                
                // Provide more specific error messages based on the error
                $errorMsg = $result['message'] ?? 'Failed to convert Word to PDF';
                $statusCode = 500;
                
                // Try to detect common errors and provide more helpful messages
                if (strpos($errorMsg, 'not found') !== false) {
                    $errorMsg = 'LibreOffice is not installed or not accessible. Please contact the administrator.';
                } elseif (strpos($errorMsg, 'permission') !== false) {
                    $errorMsg = 'Permission denied when accessing LibreOffice. Please check file permissions.';
                } elseif (strpos($errorMsg, 'timeout') !== false) {
                    $errorMsg = 'The conversion process timed out. Please try again with a smaller file.';
                } elseif (strpos($errorMsg, 'stat failed') !== false) {
                    $errorMsg = 'File access error. The temporary file could not be accessed.';
                } elseif (strpos($errorMsg, 'OCR') !== false) {
                    $errorMsg = 'OCR processing failed. Please try again without OCR or contact the administrator.';
                }
                
                // Clean up the temporary file
                if (file_exists($tempFilePath)) {
                    @unlink($tempFilePath);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'details' => config('app.debug') ? $result : null,
                ], $statusCode);
            }
            
            // Generate URL for the PDF file
            $outputPath = $result['output_path'] ?? '';
            $filename = basename($outputPath);
            
            // Generate a direct PDF serving URL
            $outputUrl = route('serve.pdf', ['filename' => $filename]);
            
            \Log::info('Word to PDF conversion successful', [
                'details' => $result['details'] ?? [],
                'output_url' => $outputUrl,
                'conversion_method' => $result['details']['conversion_method'] ?? 'unknown',
                'ocr_applied' => $options['use_ocr'] ?? false,
                'applied_options' => $options
            ]);
            
            // Clean up the temporary file
            if (file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Word document converted to PDF successfully' . 
                              (isset($options['use_ocr']) && $options['use_ocr'] ? ' with OCR enhancement' : ''),
                'file' => $outputUrl,
                'filename' => $filename,
                'details' => $result['details'] ?? [],
                'options_used' => $options
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception in Word to PDF conversion', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to convert Word to PDF: ' . $e->getMessage(),
                'details' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }
    
    /**
     * Convert PowerPoint to PDF
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function convertPptToPdf(Request $request)
    {
        try {
            // Set a higher execution time limit for this process
            ini_set('max_execution_time', 600); // 10 minutes
            set_time_limit(600); // 10 minutes
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:ppt,pptx,odp|max:50000', // 50MB limit
                'quality' => 'nullable|string|in:standard,high,very_high',
                'include_notes' => 'nullable|boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Get the uploaded file
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $originalExtension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            
            // Validate file type
            if (!in_array(strtolower($originalExtension), ['ppt', 'pptx', 'odp'])) {
                \Log::warning('PowerPoint to PDF conversion - Invalid file extension', [
                    'extension' => $originalExtension,
                    'mime_type' => $mimeType,
                    'original_name' => $originalName
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Only PPT, PPTX, and ODP files are supported',
                ], 400);
            }
            
            // Copy the uploaded file to a more permanent location rather than using the temp file directly
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Create a unique filename to avoid collisions
            $tempFilename = uniqid('ppt_') . '.' . $originalExtension;
            $tempFilePath = $tempDir . '/' . $tempFilename;
            
            // Copy the uploaded file to our temp location
            if (!copy($file->getRealPath(), $tempFilePath)) {
                \Log::error('PowerPoint to PDF conversion - Failed to copy uploaded file', [
                    'source' => $file->getRealPath(),
                    'destination' => $tempFilePath,
                    'temp_file_exists' => file_exists($file->getRealPath()),
                    'temp_file_readable' => is_readable($file->getRealPath()),
                    'temp_dir_writable' => is_writable($tempDir)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process uploaded file. Please try again.',
                ], 500);
            }
            
            // Verify the file was copied successfully
            if (!file_exists($tempFilePath) || !is_readable($tempFilePath)) {
                \Log::error('PowerPoint to PDF conversion - Failed to access copied file', [
                    'destination' => $tempFilePath,
                    'exists' => file_exists($tempFilePath),
                    'readable' => is_readable($tempFilePath)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process uploaded file. Please try again.',
                ], 500);
            }
            
            // Create output file name (preserve original name but change extension)
            $outputBaseFilename = pathinfo($originalName, PATHINFO_FILENAME);
            $outputFilename = $outputBaseFilename . '_' . uniqid() . '.pdf';
            $outputDir = storage_path('app/public/pdf');
            
            // Ensure output directory exists
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            $outputPath = $outputDir . '/' . $outputFilename;
            
            // Get conversion options
            $quality = $request->input('quality', 'standard');
            $includeNotes = $request->boolean('include_notes', false);
            
            // Detect Java path for notes export
            $javaPath = null;
            if ($includeNotes) {
                // Try to find Java
                $javaPathFinderResult = $this->findJavaPath();
                if ($javaPathFinderResult['found']) {
                    $javaPath = $javaPathFinderResult['path'];
                    \Log::info('Found Java for notes export: ' . $javaPath);
                }
            }
            
            // Process the conversion using our service
            $pptToPdfService = new PptToPdfService(null, $javaPath);
            $success = $pptToPdfService->process($tempFilePath, $outputPath, [
                'quality' => $quality,
                'include_notes' => $includeNotes
            ]);
            
            if ($success) {
                // Generate a URL for the converted PDF
                $pdfUrl = asset('storage/pdf/' . $outputFilename);
                
                // Optionally delete the temporary file
                if (file_exists($tempFilePath)) {
                    unlink($tempFilePath);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'PowerPoint converted to PDF successfully',
                    'file_url' => $pdfUrl,
                    'original_name' => $originalName,
                    'pdf_name' => $outputFilename,
                    'options' => [
                        'quality' => $quality,
                        'include_notes' => $includeNotes
                    ]
                ]);
            } else {
                // Get info about the failure
                $info = $pptToPdfService->getInfo();
                
                \Log::error('PowerPoint to PDF conversion failed', [
                    'input_file' => $tempFilePath,
                    'output_file' => $outputPath,
                    'info' => $info
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to convert PowerPoint to PDF. ' . ($info['message'] ?? 'Unknown error.'),
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Exception during PowerPoint to PDF conversion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing PowerPoint to PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Helper to find Java path
     * 
     * @return array with found status and path
     */
    protected function findJavaPath(): array
    {
        $commonJavaPaths = [
            'C:\\Program Files\\Java\\jre*\\bin\\java.exe',
            'C:\\Program Files\\Java\\jdk*\\bin\\java.exe',
            'C:\\Program Files (x86)\\Java\\jre*\\bin\\java.exe',
            'C:\\Program Files (x86)\\Java\\jdk*\\bin\\java.exe',
            'C:\\ProgramData\\Oracle\\Java\\javapath\\java.exe'
        ];
        
        foreach ($commonJavaPaths as $pattern) {
            $matches = glob($pattern);
            if (!empty($matches)) {
                $javaPath = $matches[0];
                \Log::info('Direct Java path found: ' . $javaPath);
                return ['found' => true, 'path' => $javaPath];
            }
        }
        
        // Try using 'java' from PATH
        try {
            $process = new Process(['java', '-version']);
            $process->setTimeout(15);
            $process->run();
            
            if ($process->isSuccessful()) {
                \Log::info('Java found in PATH');
                return ['found' => true, 'path' => 'java'];
            }
        } catch (\Exception $e) {
            \Log::warning('Error checking Java in PATH: ' . $e->getMessage());
        }
        
        return ['found' => false, 'path' => null];
    }
    
    /**
     * Check if LibreOffice is properly installed and accessible
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkLibreOffice()
    {
        $diagnostics = [
            'libreoffice_paths_checked' => [],
            'libreoffice_found' => false,
            'libreoffice_path' => null,
        ];
        
        // Use the WordToPdfService to detect LibreOffice
        $wordToPdfService = $this->pdfServiceFactory->getService('word_to_pdf');
        
        // Check if the service has the detectLibreOfficePath method
        if (method_exists($wordToPdfService, 'detectLibreOfficePath')) {
            $libreOfficePath = $wordToPdfService->detectLibreOfficePath();
            
            // Update diagnostics
            $diagnostics['libreoffice_found'] = $libreOfficePath !== null;
            $diagnostics['libreoffice_path'] = $libreOfficePath;
            
            // Get the paths that were checked
            if (method_exists($wordToPdfService, 'getPathsChecked')) {
                $diagnostics['libreoffice_paths_checked'] = $wordToPdfService->getPathsChecked();
            }
        }
        
        return response()->json([
            'success' => true,
            'diagnostics' => $diagnostics
        ]);
    }
    
    /**
     * Check if GhostScript and Tesseract are properly installed and accessible
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkToolsStatus()
    {
        $diagnostics = [
            'ghostscript' => [
                'found' => false,
                'path' => null,
                'version' => null,
            ],
            'tesseract' => [
                'found' => false,
                'path' => null,
                'version' => null,
            ],
            'libreoffice' => [
                'found' => false,
                'path' => null,
            ],
        ];
        
        // Check GhostScript
        $gsPath = null;
        if (isset($this->pdfServiceFactory->getBinaryPaths()['ghostscript'])) {
            $gsPath = $this->pdfServiceFactory->getBinaryPaths()['ghostscript'];
            $diagnostics['ghostscript']['found'] = true;
            $diagnostics['ghostscript']['path'] = $gsPath;
            
            // Try to get version
            try {
                if (function_exists('exec')) {
                    $output = [];
                    $returnVar = 0;
                    exec('"' . $gsPath . '" --version 2>&1', $output, $returnVar);
                    
                    if ($returnVar === 0 && !empty($output[0])) {
                        $diagnostics['ghostscript']['version'] = $output[0];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to get GhostScript version', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Check Tesseract
        $tesseractPath = null;
        if (isset($this->pdfServiceFactory->getBinaryPaths()['tesseract'])) {
            $tesseractPath = $this->pdfServiceFactory->getBinaryPaths()['tesseract'];
            $diagnostics['tesseract']['found'] = true;
            $diagnostics['tesseract']['path'] = $tesseractPath;
            
            // Try to get version
            try {
                if (function_exists('exec')) {
                    $output = [];
                    $returnVar = 0;
                    exec('"' . $tesseractPath . '" --version 2>&1', $output, $returnVar);
                    
                    if ($returnVar === 0 && !empty($output[0])) {
                        $diagnostics['tesseract']['version'] = $output[0];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to get Tesseract version', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Check LibreOffice (reuse existing method)
        if (method_exists($this, 'checkLibreOffice')) {
            $libreOfficeCheck = $this->checkLibreOffice()->getData(true);
            if (isset($libreOfficeCheck['diagnostics'])) {
                $diagnostics['libreoffice']['found'] = $libreOfficeCheck['diagnostics']['libreoffice_found'] ?? false;
                $diagnostics['libreoffice']['path'] = $libreOfficeCheck['diagnostics']['libreoffice_path'] ?? null;
            }
        }
        
        return response()->json([
            'success' => true,
            'diagnostics' => $diagnostics
        ]);
    }
    
    /**
     * Check if the server supports PPT to PDF conversion with various options
     * 
     * @return JsonResponse
     */
    public function checkPptToPdfSupport()
    {
        try {
            // Initialize support flags
            $libreOfficeInstalled = false;
            $javaInstalled = false;
            $tempDirWritable = is_writable(sys_get_temp_dir());
            $storageDirWritable = is_writable(storage_path('app/public'));
            
            // Advanced debugging
            $diagnostics = [
                'php_user' => exec('whoami'),
                'php_version' => phpversion(),
                'os' => PHP_OS,
                'libreoffice_paths_checked' => [],
                'java_paths_checked' => [],
                'environment' => [
                    'JAVA_HOME' => getenv('JAVA_HOME'),
                    'JRE_HOME' => getenv('JRE_HOME'),
                    'PATH' => getenv('PATH'),
                ]
            ];
            
            // Check for LibreOffice/OpenOffice installation - try common paths
            $possiblePaths = [
                // Linux paths
                '/usr/bin/libreoffice',
                '/usr/bin/soffice',
                '/usr/lib/libreoffice/program/soffice',
                // Windows paths
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files\\OpenOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\OpenOffice\\program\\soffice.exe',
                // Additional Windows paths
                'C:\\Program Files\\LibreOffice\\program\\soffice.bin',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.bin'
            ];
            
            $diagnostics['libreoffice_paths_checked'] = $possiblePaths;
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $libreOfficeInstalled = true;
                    $diagnostics['libreoffice_found'] = true;
                    $diagnostics['libreoffice_path'] = $path;
                    \Log::info('LibreOffice found at: ' . $path);
                    
                    // Get version information
                    try {
                        $process = new Process([$path, '--version']);
                        $process->setTimeout(15);
                        $process->run();
                        $versionOutput = $process->isSuccessful() ? $process->getOutput() : 'Unknown';
                        $diagnostics['libreoffice_version'] = $versionOutput;
                        \Log::info('LibreOffice version: ' . $versionOutput);
                    } catch (\Exception $e) {
                        $diagnostics['libreoffice_version_error'] = $e->getMessage();
                        \Log::warning('Failed to get LibreOffice version', ['error' => $e->getMessage()]);
                    }
                    
                    break;
                }
            }
            
            // Check Java installation - Method 1: Direct paths
            $javaPaths = [
                'C:\\Program Files\\Java\\jre*\\bin\\java.exe',
                'C:\\Program Files\\Java\\jdk*\\bin\\java.exe',
                'C:\\Program Files (x86)\\Java\\jre*\\bin\\java.exe',
                'C:\\Program Files (x86)\\Java\\jdk*\\bin\\java.exe',
                'C:\\ProgramData\\Oracle\\Java\\javapath\\java.exe'
            ];
            
            $diagnostics['java_paths_checked'] = $javaPaths;
            $javaFound = false;
            $javaPath = null;
            
            foreach ($javaPaths as $pattern) {
                $matches = glob($pattern);
                if (!empty($matches)) {
                    $javaPath = $matches[0];
                    $diagnostics['java_found'] = true;
                    $diagnostics['java_path'] = $javaPath;
                    
                    // Test running Java directly
                    $output = [];
                    $returnVar = 0;
                    exec('"' . $javaPath . '" -version 2>&1', $output, $returnVar);
                    
                    if ($returnVar === 0) {
                        $javaInstalled = true;
                        $javaVersionOutput = implode("\n", $output);
                        $diagnostics['java_version'] = $javaVersionOutput;
                        \Log::info('Java installed (direct path): ' . $javaVersionOutput);
                        \Log::info('Java path: ' . $javaPath);
                        $javaFound = true;
                        break;
                    } else {
                        $diagnostics['java_direct_error'] = implode("\n", $output);
                    }
                }
            }
            
            // Method 2: Try using 'java' from PATH if not found directly
            if (!$javaFound) {
                try {
                    $process = new Process(['java', '-version']);
                    $process->setTimeout(15);
                    $process->run();
                    
                    if ($process->isSuccessful()) {
                        $javaInstalled = true;
                        $javaVersionOutput = $process->getErrorOutput() ?: $process->getOutput();
                        $diagnostics['java_version'] = $javaVersionOutput;
                        \Log::info('Java installed (PATH): ' . $javaVersionOutput);
                    } else {
                        $diagnostics['java_path_error'] = $process->getErrorOutput();
                        \Log::warning('Java not found in PATH: ' . $process->getErrorOutput());
                    }
                } catch (\Exception $e) {
                    $diagnostics['java_exception'] = $e->getMessage();
                    \Log::warning('Exception checking for Java', ['error' => $e->getMessage()]);
                }
            }
            
            // Method 3: Try setting JAVA_HOME explicitly
            if (!$javaInstalled && $javaPath) {
                try {
                    $javaDir = dirname($javaPath);
                    $javaHome = dirname($javaDir);
                    
                    putenv("JAVA_HOME={$javaHome}");
                    putenv("JRE_HOME={$javaHome}");
                    putenv("PATH={$javaDir}" . PATH_SEPARATOR . getenv('PATH'));
                    
                    $diagnostics['java_home_set'] = true;
                    $diagnostics['java_home_value'] = $javaHome;
                    
                    $process = new Process(['java', '-version']);
                    $process->setTimeout(15);
                    $process->run();
                    
                    if ($process->isSuccessful()) {
                        $javaInstalled = true;
                        $javaVersionOutput = $process->getErrorOutput() ?: $process->getOutput();
                        $diagnostics['java_version_after_env'] = $javaVersionOutput;
                        \Log::info('Java installed (after setting ENV): ' . $javaVersionOutput);
                    } else {
                        $diagnostics['java_env_error'] = $process->getErrorOutput();
                    }
                } catch (\Exception $e) {
                    $diagnostics['java_env_exception'] = $e->getMessage();
                }
            }
            
            // Run a more thorough test of LibreOffice PDF export capabilities
            $exportNotesSupported = false;
            if ($libreOfficeInstalled && $javaInstalled) {
                try {
                    // Direct test - if both Java and LibreOffice are installed
                    $exportNotesSupported = true;
                    
                    \Log::info('Export notes support determined', [
                        'supported' => $exportNotesSupported,
                        'java_installed' => $javaInstalled,
                        'java_path' => $javaPath
                    ]);
                } catch (\Exception $e) {
                    $diagnostics['notes_error'] = $e->getMessage();
                    \Log::warning('Error checking LibreOffice PDF export capabilities', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Check for quality options support (generally available in most versions)
            $qualitySettingsSupported = $libreOfficeInstalled;
            
            // Available features
            $features = [
                'base_conversion' => $libreOfficeInstalled,
                'quality_settings' => $qualitySettingsSupported,
                'include_notes' => $exportNotesSupported && $javaInstalled,
                'temp_dir_writable' => $tempDirWritable,
                'storage_dir_writable' => $storageDirWritable
            ];
            
            // Log result for debugging
            \Log::info('PPT to PDF support check result', $features);
            
            return response()->json([
                'success' => true,
                'supported' => $libreOfficeInstalled,
                'features' => $features,
                'diagnostics' => $diagnostics
            ]);
        } catch (\Exception $e) {
            \Log::error('Error checking PPT to PDF support', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error checking PPT to PDF support: ' . $e->getMessage(),
                'supported' => false
            ], 500);
        }
    }
    
    /**
     * Check if Word to PDF conversion is supported
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkWordToPdfSupport()
    {
        try {
            // Initialize support flags
            $libreOfficeInstalled = false;
            $msWordInstalled = false;
            $phpWordInstalled = class_exists('\PhpOffice\PhpWord\IOFactory');
            $tempDirWritable = false;
            $storageDirWritable = false;
            
            // Check if directories are writable
            try {
                $tempDirWritable = is_writable(sys_get_temp_dir());
                $storageDirWritable = is_writable(storage_path('app/public'));
            } catch (\Exception $e) {
                \Log::warning('Error checking directory permissions: ' . $e->getMessage());
            }
            
            // Diagnostic information
            $diagnostics = [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'temp_dir_writable' => $tempDirWritable,
                'storage_dir_writable' => $storageDirWritable,
                'php_word_available' => $phpWordInstalled
            ];
            
            // Check for LibreOffice/OpenOffice installation - try common paths
            $possiblePaths = [
                // Linux paths
                '/usr/bin/libreoffice',
                '/usr/bin/soffice',
                '/usr/lib/libreoffice/program/soffice',
                // Windows paths
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files\\OpenOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\OpenOffice\\program\\soffice.exe',
                // Additional Windows paths
                'C:\\Program Files\\LibreOffice\\program\\soffice.bin',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.bin'
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $libreOfficeInstalled = true;
                    $diagnostics['libreoffice_path'] = $path;
                    
                    // Get version information
                    try {
                        $process = new Process([$path, '--version']);
                        $process->setTimeout(15);
                        $process->run();
                        $versionOutput = $process->isSuccessful() ? $process->getOutput() : 'Unknown';
                        $diagnostics['libreoffice_version'] = $versionOutput;
                    } catch (\Exception $e) {
                        $diagnostics['libreoffice_version_error'] = $e->getMessage();
                    }
                    
                    break;
                }
            }
            
            // Check for MS Word COM object (Windows only)
            if (PHP_OS === 'WINNT' || PHP_OS === 'Windows' || PHP_OS === 'WIN32') {
                try {
                    if (class_exists('COM')) {
                        try {
                            $msWord = new \COM('Word.Application', null, CP_UTF8);
                            if ($msWord) {
                                $msWordInstalled = true;
                                $msWord->Quit();
                                $diagnostics['ms_word_available'] = true;
                            }
                        } catch (\Exception $e) {
                            $diagnostics['ms_word_error'] = $e->getMessage();
                        }
                    }
                } catch (\Exception $e) {
                    $diagnostics['com_error'] = $e->getMessage();
                }
            }
            
            // Check for OCR support - check if Tesseract OCR is installed
            $ocrSupported = false;
            $tesseractInstalled = false;
            
            // Check for Tesseract OCR
            $tesseractPaths = [
                // Windows paths
                'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
                'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
                // Linux paths
                '/usr/bin/tesseract',
                '/usr/local/bin/tesseract'
            ];
            
            foreach ($tesseractPaths as $path) {
                if (file_exists($path)) {
                    $tesseractInstalled = true;
                    $diagnostics['tesseract_path'] = $path;
                    break;
                }
            }
            
            // If not found directly, try through system PATH
            if (!$tesseractInstalled && function_exists('exec')) {
                try {
                    $process = new Process(['tesseract', '--version']);
                    $process->setTimeout(10);
                    $process->run();
                    
                    if ($process->isSuccessful()) {
                        $tesseractInstalled = true;
                        $tesseractOutput = $process->getOutput() ?: $process->getErrorOutput();
                        $diagnostics['tesseract_version'] = $tesseractOutput;
                    }
                } catch (\Exception $e) {
                    $diagnostics['tesseract_error'] = $e->getMessage();
                }
            }
            
            // Check if OcrService is available
            $ocrServiceAvailable = false;
            try {
                $ocrService = app()->make('App\Services\Pdf\Ocr\OcrService');
                if ($ocrService) {
                    $ocrServiceAvailable = true;
                    $diagnostics['ocr_service_available'] = true;
                    
                    // Check if tesseract is available through the OCR service
                    try {
                        // Create a new instance to ensure fresh detection
                        $tesseractPath = $ocrService->detectTesseractPath();
                        
                        if ($tesseractPath && file_exists($tesseractPath)) {
                            $tesseractInstalled = true;
                            $diagnostics['tesseract_path_via_service'] = $tesseractPath;
                            
                            // Test tesseract by running a simple command
                            $process = new Process([$tesseractPath, '--version']);
                            $process->setTimeout(10);
                            $process->run();
                            
                            if ($process->isSuccessful()) {
                                $tesseractOutput = $process->getOutput() ?: $process->getErrorOutput();
                                $diagnostics['tesseract_version'] = $tesseractOutput;
                                \Log::info('Tesseract version detected: ' . $tesseractOutput);
                            } else {
                                \Log::warning('Tesseract found but not executable: ' . $process->getErrorOutput());
                                $diagnostics['tesseract_exec_error'] = $process->getErrorOutput();
                                $tesseractInstalled = false;
                            }
                        }
                    } catch (\Exception $e) {
                        $diagnostics['tesseract_detection_error'] = $e->getMessage();
                        \Log::error('Error detecting Tesseract: ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                $diagnostics['ocr_service_error'] = $e->getMessage();
                \Log::error('Error accessing OCR service: ' . $e->getMessage());
            }
            
            // OCR is supported if both Tesseract and OcrService are available
            $ocrSupported = $tesseractInstalled && $ocrServiceAvailable;
            
            // Force OCR to true for testing if needed
            // $ocrSupported = true;
            
            // Log the OCR support status
            \Log::info('OCR support status', [
                'tesseract_installed' => $tesseractInstalled,
                'ocr_service_available' => $ocrServiceAvailable,
                'ocr_supported' => $ocrSupported
            ]);
            
            // Check quality settings support
            $qualitySettingsSupported = $libreOfficeInstalled || $msWordInstalled;
            
            // Available features
            $features = [
                'base_conversion' => $libreOfficeInstalled || $msWordInstalled || $phpWordInstalled,
                'quality_settings' => $qualitySettingsSupported,
                'preserve_formatting' => $libreOfficeInstalled || $msWordInstalled,
                'page_orientation' => $libreOfficeInstalled,
                'page_size' => $libreOfficeInstalled,
                'margins' => $libreOfficeInstalled,
                'optimize_for_printing' => $libreOfficeInstalled,
                'ocr_support' => $ocrSupported,
                'temp_dir_writable' => $tempDirWritable,
                'storage_dir_writable' => $storageDirWritable
            ];
            
            // Log result for debugging
            \Log::info('Word to PDF support check result', [
                'features' => $features,
                'diagnostics' => $diagnostics
            ]);
            
            return response()->json([
                'success' => true,
                'supported' => $features['base_conversion'],
                'features' => $features,
                'diagnostics' => $diagnostics
            ]);
        } catch (\Exception $e) {
            \Log::error('Error checking Word to PDF support', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error checking Word to PDF support: ' . $e->getMessage(),
                'supported' => false
            ], 500);
        }
    }
    
    /**
     * Detect Tesseract OCR installation path
     * 
     * @return string|null
     */
    protected function detectTesseractPath(): ?string
    {
        // Common paths for Tesseract installation
        $commonPaths = [
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
        ];
        
        // Check if tesseract exists in any of the common paths
        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Try to find using 'where' on Windows or 'which' on Unix
        $osCommand = PHP_OS === 'WINNT' ? 'where tesseract' : 'which tesseract';
        $output = trim(shell_exec($osCommand));
        
        if ($output && file_exists($output)) {
            return $output;
        }
        
        return null;
    }
    
    /**
     * Detect Ghostscript installation path
     * 
     * @return string|null
     */
    protected function detectGhostscriptPath(): ?string
    {
        // Common paths for Ghostscript installation
        $commonPaths = [
            'C:\\Program Files\\gs\\gs9.54.0\\bin\\gswin64c.exe', // Example version
            'C:\\Program Files\\gs\\gs*\\bin\\gswin64c.exe', // Wildcard for version
            'C:\\Program Files (x86)\\gs\\gs*\\bin\\gswin32c.exe',
            '/usr/bin/gs',
            '/usr/local/bin/gs',
        ];
        
        // Check if gs exists in any of the common paths
        foreach ($commonPaths as $path) {
            if (strpos($path, '*') !== false) {
                // Handle wildcard paths with glob
                $matchingPaths = glob($path);
                if (!empty($matchingPaths)) {
                    return $matchingPaths[0];
                }
            } elseif (file_exists($path)) {
                return $path;
            }
        }
        
        // Try to find using 'where' on Windows or 'which' on Unix
        $osCommand = PHP_OS === 'WINNT' ? 'where gswin64c 2>NUL || where gswin32c' : 'which gs';
        $output = trim(shell_exec($osCommand));
        
        if ($output && file_exists($output)) {
            return $output;
        }
        
        return null;
    }
    
    /**
     * Get Tesseract version
     * 
     * @param string $tesseractPath
     * @return string
     */
    protected function getTesseractVersion(string $tesseractPath): string
    {
        $command = '"' . $tesseractPath . '" --version 2>&1';
        $output = shell_exec($command);
        
        if (preg_match('/tesseract\s+(\d+\.\d+\.\d+)/i', $output, $matches)) {
            return $matches[1];
        }
        
        return 'unknown';
    }
    
    /**
     * Get Ghostscript version
     * 
     * @param string $ghostscriptPath
     * @return string
     */
    protected function getGhostscriptVersion(string $ghostscriptPath): string
    {
        $command = '"' . $ghostscriptPath . '" --version 2>&1';
        $output = trim(shell_exec($command));
        
        if (preg_match('/(\d+\.\d+\.\d+)/i', $output, $matches)) {
            return $matches[1];
        }
        
        return 'unknown';
    }
    
    /**
     * Get available Tesseract languages
     * 
     * @param string $tesseractPath
     * @return array
     */
    protected function getAvailableTesseractLanguages(string $tesseractPath): array
    {
        // Common paths for Tesseract language data
        $langPaths = [
            dirname($tesseractPath) . '\\tessdata',
            dirname($tesseractPath) . '/tessdata',
            'C:\\Program Files\\Tesseract-OCR\\tessdata',
            '/usr/share/tesseract-ocr/tessdata',
            '/usr/local/share/tessdata',
        ];
        
        $availableLanguages = [];
        
        foreach ($langPaths as $path) {
            if (is_dir($path)) {
                $langFiles = glob($path . '/*.traineddata');
                
                if (!empty($langFiles)) {
                    foreach ($langFiles as $langFile) {
                        $langCode = pathinfo($langFile, PATHINFO_FILENAME);
                        if (!in_array($langCode, ['osd', 'equ'])) { // Skip non-language files
                            $availableLanguages[] = $langCode;
                        }
                    }
                    
                    break; // Found languages, no need to check other paths
                }
            }
        }
        
        // Ensure at least English is listed if no languages found
        if (empty($availableLanguages)) {
            $availableLanguages = ['eng'];
        }
        
        return $availableLanguages;
    }
    
    /**
     * Process a Excel to PDF conversion
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function excelToPdf(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv,xlsm|max:'.config('app.max_file_size'),
            'quality' => 'sometimes|in:low,medium,high',
            'orientation' => 'sometimes|in:portrait,landscape',
            'page_size' => 'sometimes|in:A4,A3,Letter,Legal',
            'margins' => 'sometimes|in:normal,narrow,wide',
            'fitTo' => 'sometimes|in:width,height,page',
            'worksheetOption' => 'sometimes|in:all,active',
            'optimize_for_printing' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Get the uploaded file
            $file = $request->file('excel_file');
            $originalName = $file->getClientOriginalName();
            
            // Log the conversion request
            Log::info('Excel to PDF conversion request', [
                'originalName' => $originalName,
                'fileSize' => $file->getSize(),
                'fileMimeType' => $file->getMimeType(),
            ]);
            
            // Store file in a more stable temporary location
            $temporaryStorage = app(TemporaryStorage::class);
            $tempPath = $temporaryStorage->storeUploadedFile($file);
            
            // Verify the file exists before proceeding
            if (!file_exists($tempPath)) {
                Log::error('Temporary file not found after upload', [
                    'tempPath' => $tempPath,
                    'originalName' => $originalName
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'File upload failed. The temporary file could not be created.'
                ], 500);
            }
            
            Log::info('Excel file stored in temporary location', [
                'tempPath' => $tempPath,
                'originalName' => $originalName,
                'fileExists' => file_exists($tempPath),
                'fileSize' => file_exists($tempPath) ? filesize($tempPath) : 'N/A'
            ]);

            // Set up conversion options
            $options = [
                'quality' => $request->input('quality', 'medium'),
                'orientation' => $request->input('orientation', 'portrait'),
                'page_size' => $request->input('page_size', 'A4'),
                'margins' => $request->input('margins', 'normal'),
                'fitTo' => $request->input('fitTo', 'width'),
                'worksheetOption' => $request->input('worksheetOption', 'all'),
                'optimize_for_printing' => $request->input('optimize_for_printing', true),
                'original_filename' => $originalName,
            ];

            // Process the conversion
            $result = $this->pdfServiceFactory->excelToPdf($tempPath, $options);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Excel successfully converted to PDF',
                    'download_url' => $result['download_url']
                ]);
            } else {
                Log::error('Excel to PDF conversion failed', [
                    'error' => $result['message'],
                    'tempFile' => $tempPath,
                    'originalName' => $originalName
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to convert Excel file to PDF: ' . $result['message']
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exception during Excel to PDF conversion', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while converting Excel to PDF: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if Excel to PDF conversion is supported
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkExcelToPdfSupport()
    {
        try {
            $features = [
                'php_spreadsheet_available' => class_exists('\PhpOffice\PhpSpreadsheet\IOFactory'),
                'mpdf_available' => class_exists('\Mpdf\Mpdf'),
                'dompdf_available' => class_exists('\Dompdf\Dompdf'),
                'tcpdf_available' => class_exists('\TCPDF'),
                'com_automation_available' => PHP_OS === 'WINNT' && class_exists('COM'),
                'libreoffice_available' => !empty($this->pdfServiceFactory->getBinaryPaths()['libreoffice']),
            ];
            
            // Determine overall support status
            $supported = $features['php_spreadsheet_available'] && 
                         ($features['mpdf_available'] || $features['dompdf_available'] || $features['tcpdf_available'] || 
                          $features['com_automation_available'] || $features['libreoffice_available']);
            
            // Get system diagnostics
            $diagnostics = [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'temp_dir_writable' => is_writable(sys_get_temp_dir()),
                'storage_dir_writable' => is_writable(storage_path('app')),
                'excel_handlers' => [
                    'php_spreadsheet' => $features['php_spreadsheet_available'],
                    'mpdf' => $features['mpdf_available'],
                    'dompdf' => $features['dompdf_available'],
                    'tcpdf' => $features['tcpdf_available'],
                ],
                'libreoffice_path' => $this->pdfServiceFactory->getBinaryPaths()['libreoffice'] ?? null,
            ];
            
            return response()->json([
                'success' => true,
                'supported' => $supported,
                'features' => $features,
                'diagnostics' => $diagnostics,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error checking Excel to PDF support: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error checking Excel to PDF support: ' . $e->getMessage(),
            ], 500);
        }
    }
} 