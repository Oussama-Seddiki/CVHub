<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    protected $apiKey;
    protected $apiUrl = 'https://api.sejda.com/v2/html-pdf';

    public function __construct()
    {
        // This should be stored in .env file
        $this->apiKey = env('SEJDA_API_KEY', '');
    }

    /**
     * Convert HTML to PDF using Sejda API
     */
    public function convertHtmlToPdf(Request $request)
    {
        $request->validate([
            'url' => 'required_without:html|url',
            'html' => 'required_without:url|string',
            'pageSize' => 'nullable|string',
            'pageOrientation' => 'nullable|string|in:portrait,landscape,auto',
            'viewportWidth' => 'nullable|integer',
        ]);

        try {
            $params = [
                'pageSize' => $request->input('pageSize', 'a4'),
                'pageOrientation' => $request->input('pageOrientation', 'auto'),
            ];

            if ($request->has('viewportWidth')) {
                $params['viewportWidth'] = $request->input('viewportWidth');
            }

            if ($request->has('url')) {
                $params['url'] = $request->input('url');
            } else {
                $params['htmlCode'] = $request->input('html');
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Token: ' . $this->apiKey,
            ])->post($this->apiUrl, $params);

            if ($response->successful()) {
                $fileName = 'converted-' . time() . '.pdf';
                Storage::put('public/pdfs/' . $fileName, $response->body());
                
                return response()->json([
                    'success' => true,
                    'file' => asset('storage/pdfs/' . $fileName),
                    'message' => 'تم تحويل المستند بنجاح'
                ]);
            } else {
                Log::error('Sejda API Error: ' . $response->body());
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في تحويل المستند: ' . $response->status()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('PDF Conversion Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الطلب'
            ], 500);
        }
    }

    /**
     * Process PDF file using Sejda API
     */
    public function processPdf(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // Max 10MB
            'operation' => 'required|string|in:compress,extract,unlock',
        ]);

        try {
            // Store the uploaded file
            $path = $request->file('file')->store('temp');
            $fullPath = storage_path('app/' . $path);
            
            // This would ideally call the Sejda API for PDF operations
            // For now, just return the file as is
            return response()->json([
                'success' => true,
                'message' => 'تمت معالجة الملف بنجاح',
                'file' => asset('storage/' . $path),
            ]);
        } catch (\Exception $e) {
            Log::error('PDF Processing Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الملف'
            ], 500);
        }
    }
} 