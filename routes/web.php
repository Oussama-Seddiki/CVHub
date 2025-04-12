<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Models\Subscription;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\PdfProcessingController;
use App\Http\Controllers\TestPdfController;
use App\Http\Controllers\ExcelToPdfController;

Route::get('/', function () {
    return Inertia::render('Welcome/Index', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

// Public accessible subscription plans page
Route::get('/plans', function () {
    return Inertia::render('Subscription/Plans');
})->name('subscription.plans');

// Route for checkout after selecting a plan (requires authentication)
Route::get('/subscription/checkout', function () {
    return Inertia::render('Subscription/Checkout');
})->middleware(['auth'])->name('subscription.checkout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        return Inertia::render('Dashboard', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('dashboard');

    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription');
    Route::post('/subscription/create', [SubscriptionController::class, 'create'])->name('subscription.create');
    Route::get('/subscription/payment/{subscription}', [SubscriptionController::class, 'showPayment'])
        ->name('subscription.payment');
    Route::get('/subscription/simulate-payment/{subscription}', [SubscriptionController::class, 'simulatePayment'])
        ->name('subscription.simulate-payment');
    Route::post('/subscription/confirm-payment/{subscription}', [SubscriptionController::class, 'confirmPayment'])
        ->name('subscription.confirm-payment');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Protected routes - Using auth middleware only and explicit checks in each route
Route::middleware(['auth'])->group(function () {
    // CV route
    Route::get('/cv', function () {
        $user = auth()->user();
        
        // We're no longer redirecting users without a subscription
        // Instead, we pass subscription status to the component
        return Inertia::render('CV/Index', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('cv');

    // File processing route
    Route::get('/file-processing', function () {
        $user = auth()->user();
        
        // We're no longer redirecting users without a subscription
        // Instead, we pass subscription status to the component
        return Inertia::render('FileProcessing/Index', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing');

    // File processing tool routes
    Route::get('/file-processing/merge', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/Merge', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.merge');

    Route::get('/file-processing/split', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/Split', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.split');

    Route::get('/file-processing/compress', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/Compress', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.compress');

    Route::get('/file-processing/protect', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/Protect', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.protect');

    Route::get('/file-processing/unlock', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/Unlock', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.unlock');

    // Additional file processing tool routes
    Route::get('/file-processing/remove-pages', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/RemovePages', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.remove-pages');

    Route::get('/file-processing/extract-pages', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/ExtractPages', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.extract-pages');

    Route::get('/file-processing/organize-pages', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/OrganizePages', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.organize-pages');

    Route::get('/file-processing/ocr', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/Ocr', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.ocr');

    Route::get('/file-processing/jpg-to-pdf', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/JpgToPdf', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.jpg-to-pdf');

    Route::get('/file-processing/word-to-pdf', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/WordToPdf', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.word-to-pdf');

    Route::get('/file-processing/ppt-to-pdf', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/PptToPdf', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.ppt-to-pdf');

    Route::get('/file-processing/excel-to-pdf', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/ExcelToPdf', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.excel-to-pdf');

    Route::get('/file-processing/pdf-to-jpg', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/PdfToJpg', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.pdf-to-jpg');

    Route::get('/file-processing/pdf-to-word', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/PdfToWord', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.pdf-to-word');

    Route::get('/file-processing/pdf-to-ppt', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/PdfToPpt', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.pdf-to-ppt');

    Route::get('/file-processing/pdf-to-excel', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/PdfToExcel', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.pdf-to-excel');

    Route::get('/file-processing/rotate', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/Rotate', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.rotate');

    Route::get('/file-processing/add-page-numbers', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/AddPageNumbers', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.add-page-numbers');

    Route::get('/file-processing/add-watermark', function () {
        $user = auth()->user();
        return Inertia::render('FileProcessing/AddWatermark', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at
        ]);
    })->name('file-processing.add-watermark');

    // Library route
    Route::get('/library', function () {
        $user = auth()->user();
        
        // We're no longer redirecting users without a subscription
        // Instead, we pass subscription status to the component

        // Sample documents data (you should replace this with your actual data)
        $documents = [
            [
                'id' => 1,
                'title' => 'نموذج السيرة الذاتية الاحترافي',
                'description' => 'نموذج سيرة ذاتية احترافي باللغة العربية',
                'category' => 'templates',
                'type' => 'PDF',
                'size' => '2.5MB',
                'pages' => 3,
                'thumbnail' => '/images/documents/cv-template.jpg',
                'downloadUrl' => '/documents/cv-template.pdf'
            ],
            [
                'id' => 2,
                'title' => 'دليل كتابة السيرة الذاتية',
                'description' => 'دليل شامل لكتابة سيرة ذاتية احترافية',
                'category' => 'guides',
                'type' => 'PDF',
                'size' => '1.8MB',
                'pages' => 15,
                'thumbnail' => '/images/documents/cv-guide.jpg',
                'downloadUrl' => '/documents/cv-guide.pdf'
            ],
        ];

        return Inertia::render('Library/Index', [
            'activeSubscription' => $user->isSubscribed(),
            'subscriptionStatus' => $user->subscription_status,
            'subscriptionEndsAt' => $user->subscription_ends_at,
            'documents' => $documents,
            'categories' => [
                ['id' => 'all', 'name' => 'الكل'],
                ['id' => 'documents', 'name' => 'وثائق رسمية'],
                ['id' => 'studies', 'name' => 'مذكرات دراسية'],
                ['id' => 'templates', 'name' => 'نماذج وقوالب'],
                ['id' => 'guides', 'name' => 'أدلة وكتيبات']
            ]
        ]);
    })->name('library');

    // PDF Processing Routes - these still need subscription check for API calls
    Route::post('/pdf/convert', function (Request $request) {
        $user = auth()->user();
        if (!$user->hasActiveSubscription()) {
            return response()->json(['error' => 'Subscription required'], 403);
        }
        return app()->call([App\Http\Controllers\PdfController::class, 'convertHtmlToPdf'], ['request' => $request]);
    })->name('pdf.convert');
    
    // CV Template Routes
    Route::get('/templates', [App\Http\Controllers\TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/{template}', [App\Http\Controllers\TemplateController::class, 'show'])->name('templates.show');
    Route::get('/templates/{template}/select', [App\Http\Controllers\TemplateController::class, 'select'])->name('templates.select');
    Route::get('/templates/{template}/download', [App\Http\Controllers\TemplateController::class, 'download'])->name('templates.download');
    
    // CV Creation
    Route::get('/cv/create', [App\Http\Controllers\CVController::class, 'create'])->name('cv.create');
    Route::post('/cv/store', [App\Http\Controllers\CVController::class, 'store'])->name('cv.store');
    Route::get('/cv/preview', [App\Http\Controllers\CVController::class, 'preview'])->name('cv.preview');
    Route::get('/cv/generate', [App\Http\Controllers\CVController::class, 'generate'])->name('cv.generate');
    Route::post('/cv/enhance-content', [App\Http\Controllers\CVController::class, 'enhanceContent'])->name('cv.enhance');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/templates/create', [App\Http\Controllers\TemplateController::class, 'create'])->name('admin.templates.create');
    Route::post('/admin/templates', [App\Http\Controllers\TemplateController::class, 'store'])->name('admin.templates.store');
});

Route::get('/file-processing/compress-pdf', function () {
    $user = auth()->user();
    return Inertia::render('FileProcessing/CompressPdf', [
        'activeSubscription' => $user->isSubscribed(),
        'subscriptionStatus' => $user->subscription_status,
        'subscriptionEndsAt' => $user->subscription_ends_at
    ]);
})->middleware(['auth', 'verified'])->name('file-processing.compress-pdf');

// Comment out the diagnostic route for checking API keys
// Route::get('/api-diagnostic', function () {
//     // Set content type to JSON
//     header('Content-Type: application/json');
//     
//     // Get API keys from env and config
//     $envPubKey = env('ILOVEPDF_PUBLIC_KEY');
//     $envSecKey = env('ILOVEPDF_SECRET_KEY');
//     $configPubKey = config('services.ilovepdf.public_key');
//     $configSecKey = config('services.ilovepdf.secret_key');
//     
//     // Create diagnostic info
//     $diagnosticInfo = [
//         'environment' => app()->environment(),
//         'env_keys' => [
//             'public_key_set' => !empty($envPubKey),
//             'public_key_length' => $envPubKey ? strlen($envPubKey) : 0,
//             'public_key_sample' => $envPubKey ? substr($envPubKey, 0, 15) . '...' : null,
//             'secret_key_set' => !empty($envSecKey),
//             'secret_key_length' => $envSecKey ? strlen($envSecKey) : 0,
//             'secret_key_sample' => $envSecKey ? substr($envSecKey, 0, 15) . '...' : null,
//         ],
//         'config_keys' => [
//             'public_key_set' => !empty($configPubKey),
//             'public_key_length' => $configPubKey ? strlen($configPubKey) : 0,
//             'public_key_sample' => $configPubKey ? substr($configPubKey, 0, 15) . '...' : null,
//             'secret_key_set' => !empty($configSecKey),
//             'secret_key_length' => $configSecKey ? strlen($configSecKey) : 0,
//             'secret_key_sample' => $configSecKey ? substr($configSecKey, 0, 15) . '...' : null,
//         ]
//     ];
//     
//     // Try to initialize SDK
//     try {
//         $controller = new \App\Http\Controllers\ILoveApiController();
//         $sdkStatus = $controller->checkApiStatus();
//         $diagnosticInfo['sdk_check'] = [
//             'success' => $sdkStatus->original['success'] ?? false,
//             'message' => $sdkStatus->original['message'] ?? 'Unknown status',
//         ];
//     } catch (\Exception $e) {
//         $diagnosticInfo['sdk_check'] = [
//             'success' => false,
//             'error' => $e->getMessage(),
//             'file' => $e->getFile(),
//             'line' => $e->getLine()
//         ];
//     }
//     
//     return response()->json($diagnosticInfo);
// });

// Add a simple welcome route without authentication
Route::get('/welcome-test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Welcome to CVHub API',
        'time' => now()->toDateTimeString(),
    ]);
});

// مسارات API خدمة iLovePDF
Route::prefix('api/ilovepdf')->middleware(['api'])->group(function () {
    // Remove all ILoveApiController references and replace with PdfProcessingController
    
    // Get available PDF tools
    Route::get('/status', [PdfProcessingController::class, 'tools']);
    
    // PDF processing endpoints
    Route::post('/pdf/merge', [PdfProcessingController::class, 'mergePdf']);
    Route::post('/pdf/extract-pages', [PdfProcessingController::class, 'extractPages']);
    Route::post('/pdf/to-word', [PdfProcessingController::class, 'pdfToWord']);
    Route::post('/pdf/ocr', [PdfProcessingController::class, 'ocrPdf']);
    Route::post('/pdf/password-protect', [PdfProcessingController::class, 'passwordProtectPdf']);
});

// Test route for PDF merge
Route::get('/test-pdf-merge', [TestPdfController::class, 'testPdfMerge']);

// Route for downloading extracted PDF files
Route::get('/download-extracted-pdf/{filename}', function($filename) {
    // Sanitize the filename to prevent directory traversal
    $filename = basename($filename);
    
    // Path to the file in the temp directory
    $path = storage_path('app/temp/' . $filename);
    
    // Check if file exists
    if (!file_exists($path)) {
        // Log the issue for debugging
        \Log::error('PDF file not found: ' . $path);
        abort(404, 'File not found');
    }
    
    // Return the file as a download with an appropriate filename
    return response()->download($path, 'extracted_pages.pdf', [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="extracted_pages.pdf"'
    ]);
})->name('download.extracted-pdf');

// Add a route to serve PDF files from storage
Route::get('/pdf/{filename}', function($filename) {
    $path = storage_path('app/public/pdf/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"'
    ]);
})->where('filename', '.*')->name('serve.pdf');

Route::post('/convert-excel-to-pdf', [App\Http\Controllers\ExcelToPdfController::class, 'convert'])->name('convert.excel.to.pdf');

Route::get('/api/excel-to-pdf-support', [App\Http\Controllers\ExcelToPdfController::class, 'checkSupport'])->name('excel.to.pdf.support');
