<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class CVController extends Controller
{
    /**
     * Show the CV creation form.
     */
    public function create()
    {
        // Check if a template has been selected
        if (!session()->has('selected_template')) {
            return redirect()->route('templates.index')
                ->with('error', 'Please select a template first.');
        }
        
        $templateId = session('selected_template');
        $template = Template::findOrFail($templateId);
        
        return view('cv.create', compact('template'));
    }
    
    /**
     * Process the CV data and prepare for generation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'personal_info.name' => 'required|string|max:255',
            'personal_info.email' => 'required|email|max:255',
            'personal_info.phone' => 'required|string|max:20',
            'personal_info.address' => 'nullable|string|max:255',
            'personal_info.photo' => 'nullable|image|max:2048',
            'education' => 'required|array|min:1',
            'experience' => 'required|array|min:1',
            'skills' => 'required|array|min:1',
            'languages' => 'nullable|array',
            'summary' => 'nullable|string',
        ]);
        
        // Process photo if uploaded
        $photoPath = null;
        if ($request->hasFile('personal_info.photo')) {
            $photoPath = 'cv_photos/' . time() . '_' . $request->file('personal_info.photo')->getClientOriginalName();
            $request->file('personal_info.photo')->move(public_path('cv_photos'), $photoPath);
        }
        
        // Store CV data in session
        $cvData = $validated;
        $cvData['personal_info']['photo'] = $photoPath;
        session(['cv_data' => $cvData]);
        
        // Redirect to preview
        return redirect()->route('cv.preview');
    }
    
    /**
     * Preview the CV before generation.
     */
    public function preview()
    {
        // Check if CV data exists in session
        if (!session()->has('cv_data') || !session()->has('selected_template')) {
            return redirect()->route('cv.create')
                ->with('error', 'Please fill in your CV information first.');
        }
        
        $templateId = session('selected_template');
        $template = Template::findOrFail($templateId);
        $cvData = session('cv_data');
        
        return view('cv.preview', compact('template', 'cvData'));
    }
    
    /**
     * Use ChatGPT to enhance CV content.
     */
    public function enhanceContent(Request $request)
    {
        // Use OpenAI API to enhance the content
        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'OpenAI API key not configured.'
            ], 500);
        }
        
        $content = $request->input('content');
        $type = $request->input('type'); // summary, experience, etc.
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert CV writer who helps improve professional content.'
                ],
                [
                    'role' => 'user',
                    'content' => "Enhance this {$type} section for a professional CV: {$content}"
                ]
            ],
            'temperature' => 0.7,
        ]);
        
        if ($response->successful()) {
            $result = $response->json();
            $enhancedContent = $result['choices'][0]['message']['content'] ?? null;
            
            return response()->json([
                'original' => $content,
                'enhanced' => $enhancedContent
            ]);
        }
        
        return response()->json([
            'error' => 'Failed to enhance content.',
            'details' => $response->json()
        ], 500);
    }
    
    /**
     * Generate the PDF CV.
     */
    public function generate()
    {
        // Check if CV data exists in session
        if (!session()->has('cv_data') || !session()->has('selected_template')) {
            return redirect()->route('cv.create')
                ->with('error', 'Please fill in your CV information first.');
        }
        
        $templateId = session('selected_template');
        $template = Template::findOrFail($templateId);
        $cvData = session('cv_data');
        
        // Generate PDF
        $pdf = PDF::loadView('cv.templates.' . $template->id, compact('cvData'));
        
        // Save to storage if user is logged in
        if (Auth::check()) {
            $filename = 'cv_' . time() . '.pdf';
            $pdf->save(storage_path('app/public/generated_cvs/' . $filename));
            
            // Save CV to user's history if needed
            // User::find(Auth::id())->cvs()->create([...]);
        }
        
        return $pdf->download('cv.pdf');
    }
}
