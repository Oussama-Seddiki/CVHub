<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create templates directory if it doesn't exist
        $templatesDir = public_path('templates');
        $thumbnailsDir = public_path('templates/thumbnails');
        
        if (!File::exists($templatesDir)) {
            File::makeDirectory($templatesDir, 0755, true);
        }
        
        if (!File::exists($thumbnailsDir)) {
            File::makeDirectory($thumbnailsDir, 0755, true);
        }
        
        // Sample templates
        $templates = [
            [
                'name' => 'Professional CV',
                'description' => 'Clean and professional CV template suitable for corporate jobs',
                'category' => 'professional',
                'is_premium' => false,
            ],
            [
                'name' => 'Creative CV',
                'description' => 'Eye-catching CV template for creative industries',
                'category' => 'creative',
                'is_premium' => true,
            ],
            [
                'name' => 'Academic CV',
                'description' => 'Formal CV template for academic and research positions',
                'category' => 'academic',
                'is_premium' => false,
            ],
            [
                'name' => 'Technical CV',
                'description' => 'CV template highlighting technical skills for IT professionals',
                'category' => 'technical',
                'is_premium' => false,
            ],
            [
                'name' => 'Executive CV',
                'description' => 'Premium CV template for C-level executives and senior managers',
                'category' => 'professional',
                'is_premium' => true,
            ],
        ];
        
        foreach ($templates as $template) {
            // Copy sample PDF files
            $fileName = strtolower(str_replace(' ', '_', $template['name'])) . '.pdf';
            $samplePdfPath = database_path('seeders/sample_cv.pdf');
            $templatePath = 'templates/' . $fileName;
            
            // Copy sample thumbnail files
            $thumbnailName = strtolower(str_replace(' ', '_', $template['name'])) . '.jpg';
            $sampleThumbnailPath = database_path('seeders/sample_thumbnail.jpg');
            $thumbnailPath = 'templates/thumbnails/' . $thumbnailName;
            
            // Create copies only if the sample files exist
            if (File::exists($samplePdfPath)) {
                File::copy($samplePdfPath, public_path($templatePath));
            } else {
                // If sample doesn't exist, create a placeholder PDF
                File::put(public_path($templatePath), 'Sample CV template content');
            }
            
            if (File::exists($sampleThumbnailPath)) {
                File::copy($sampleThumbnailPath, public_path($thumbnailPath));
            } else {
                // Create default thumbnail path anyway
                $thumbnailPath = 'templates/thumbnails/default.jpg';
            }
            
            // Create the template in the database
            Template::create([
                'name' => $template['name'],
                'description' => $template['description'],
                'category' => $template['category'],
                'is_premium' => $template['is_premium'],
                'file_path' => $templatePath,
                'thumbnail_path' => $thumbnailPath,
                'downloads' => rand(10, 200),
            ]);
        }
    }
}
