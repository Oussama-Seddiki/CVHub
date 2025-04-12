/**
 * Convert PPT/PPTX file to PDF
 *
 * @param Request $request
 * @return JsonResponse
 */
public function convertPptToPdf(Request $request): JsonResponse
{
    try {
        // Validate request
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:ppt,pptx,odp|max:50000',
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
        $originalFilename = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();

        // Create a unique filename for the output
        $filename = pathinfo($originalFilename, PATHINFO_FILENAME);
        $outputFilename = $filename . '_' . time() . '.pdf';

        // Get input and output paths
        $inputPath = $file->getRealPath();
        $outputDir = storage_path('app/public/converted');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $outputPath = $outputDir . '/' . $outputFilename;

        // Get and validate options
        $quality = $request->input('quality', 'standard');
        $includeNotes = $request->boolean('include_notes', false);

        // Valid quality values
        $validQualities = ['standard', 'high', 'very_high'];
        if (!in_array($quality, $validQualities)) {
            $quality = 'standard';
        }

        // Log the conversion request
        Log::info('PowerPoint to PDF conversion request received', [
            'original_filename' => $originalFilename,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'options' => [
                'quality' => $quality,
                'include_notes' => $includeNotes
            ]
        ]);

        // Create the service and process the file
        $service = new PptToPdfService();
        $result = $service->process($inputPath, $outputPath, [
            'quality' => $quality,
            'include_notes' => $includeNotes
        ]);

        // Check the result
        if ($result) {
            $info = $service->getInfo();
            $publicPath = '/storage/converted/' . $outputFilename;

            return response()->json([
                'success' => true,
                'message' => 'PowerPoint converted to PDF successfully',
                'file' => [
                    'name' => $outputFilename,
                    'original_name' => $originalFilename,
                    'size' => filesize($outputPath),
                    'path' => $publicPath,
                    'url' => url($publicPath)
                ],
                'details' => [
                    'quality' => $quality,
                    'include_notes' => $includeNotes
                ]
            ]);
        } else {
            $info = $service->getInfo();
            Log::error('PowerPoint to PDF conversion failed', $info);

            return response()->json([
                'success' => false,
                'message' => $info['message'] ?? 'Failed to convert PowerPoint to PDF',
                'details' => $info['details'] ?? []
            ], 500);
        }
    } catch (\Exception $e) {
        Log::error('PowerPoint to PDF conversion error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error processing PowerPoint file: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ], 500);
    }
} 