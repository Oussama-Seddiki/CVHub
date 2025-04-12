<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    /**
     * Display a listing of the templates.
     */
    public function index(Request $request)
    {
        $category = $request->query('category', 'all');
        
        if ($category !== 'all') {
            $templates = Template::where('category', $category)->get();
        } else {
            $templates = Template::all();
        }
        
        $categories = Template::select('category')->distinct()->pluck('category');
        
        return view('templates.index', compact('templates', 'categories', 'category'));
    }

    /**
     * Show the template details.
     */
    public function show(Template $template)
    {
        return view('templates.show', compact('template'));
    }

    /**
     * Select a template for CV creation.
     */
    public function select(Template $template)
    {
        // Store the selected template ID in the session
        session(['selected_template' => $template->id]);
        
        // Redirect to the CV creation page
        return redirect()->route('cv.create');
    }

    /**
     * Download a template.
     */
    public function download(Template $template)
    {
        // Increment the download count
        $template->incrementDownloads();
        
        // Return the file for download
        return response()->download(public_path($template->file_path));
    }
    
    /**
     * Admin method: Create template form
     */
    public function create()
    {
        return view('templates.create');
    }
    
    /**
     * Admin method: Store a new template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:50',
            'is_premium' => 'boolean',
            'template_file' => 'required|mimes:pdf|max:5120', // 5MB max
            'thumbnail' => 'required|image|max:2048', // 2MB max
        ]);
        
        // Handle file uploads
        $templatePath = 'templates/' . time() . '_' . $request->file('template_file')->getClientOriginalName();
        $thumbnailPath = 'templates/thumbnails/' . time() . '_' . $request->file('thumbnail')->getClientOriginalName();
        
        $request->file('template_file')->move(public_path('templates'), $templatePath);
        $request->file('thumbnail')->move(public_path('templates/thumbnails'), $thumbnailPath);
        
        // Create the template
        Template::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'is_premium' => $validated['is_premium'] ?? false,
            'file_path' => $templatePath,
            'thumbnail_path' => $thumbnailPath,
        ]);
        
        return redirect()->route('templates.index')
            ->with('success', 'Template created successfully.');
    }
}
