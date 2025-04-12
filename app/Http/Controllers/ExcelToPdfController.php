<?php

namespace App\Http\Controllers;

use App\Services\ExcelToPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Dompdf\Dompdf;

class ExcelToPdfController extends Controller
{
    protected $excelToPdfService;

    public function __construct(ExcelToPdfService $excelToPdfService)
    {
        $this->excelToPdfService = $excelToPdfService;
    }

    public function checkSupport()
    {
        try {
            // Check if required PHP extensions are enabled
            $requiredExtensions = [
                'zip',
                'xml',
                'gd',
                'iconv',
                'simplexml',
                'xmlreader',
                'zlib'
            ];

            $missingExtensions = [];
            foreach ($requiredExtensions as $extension) {
                if (!extension_loaded($extension)) {
                    $missingExtensions[] = $extension;
                }
            }

            // Check if required classes exist
            $requiredClasses = [
                IOFactory::class,
                Dompdf::class
            ];

            $missingClasses = [];
            foreach ($requiredClasses as $class) {
                if (!class_exists($class)) {
                    $missingClasses[] = $class;
                }
            }

            $isSupported = empty($missingExtensions) && empty($missingClasses);

            return response()->json([
                'success' => true,
                'supported' => $isSupported,
                'missing_extensions' => $missingExtensions,
                'missing_classes' => $missingClasses,
                'message' => $isSupported 
                    ? 'Excel to PDF conversion is supported' 
                    : 'Some requirements are missing: ' . implode(', ', array_merge($missingExtensions, $missingClasses))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'supported' => false,
                'error' => $e->getMessage(),
                'message' => 'Error checking support: ' . $e->getMessage()
            ], 500);
        }
    }

    public function convert(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            // Store the uploaded Excel file
            $excelPath = $request->file('excel_file')->store('temp');
            $excelFullPath = storage_path('app/' . $excelPath);

            // Generate PDF path
            $pdfPath = storage_path('app/public/' . pathinfo($excelPath, PATHINFO_FILENAME) . '.pdf');

            // Convert Excel to PDF
            $this->excelToPdfService->convertExcelToPdf($excelFullPath, $pdfPath);

            // Clean up the temporary Excel file
            Storage::delete($excelPath);

            // Return the PDF file
            return response()->download($pdfPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Conversion failed: ' . $e->getMessage()
            ], 500);
        }
    }
} 