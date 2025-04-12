<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CV extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_id',
        'title',
        'personal_info',
        'education',
        'experience',
        'skills',
        'languages',
        'certifications',
        'photo_path',
        'pdf_path',
        'docx_path',
        'api_request_id',
        'status',
        'error_message',
        'last_generated_at'
    ];

    protected $casts = [
        'personal_info' => 'array',
        'education' => 'array',
        'experience' => 'array',
        'skills' => 'array',
        'languages' => 'array',
        'certifications' => 'array',
        'last_generated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the CV.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the template used for this CV.
     */
    public function template()
    {
        return $this->belongsTo(ResumeTemplate::class, 'template_id');
    }

    /**
     * Check if the CV is ready for generation
     */
    public function isReadyForGeneration(): bool
    {
        return $this->status === 'draft' && 
               !empty($this->personal_info) && 
               !empty($this->education) && 
               !empty($this->experience) && 
               !empty($this->skills);
    }

    /**
     * Check if the CV generation is in progress
     */
    public function isGenerating(): bool
    {
        return $this->status === 'generating';
    }

    /**
     * Check if the CV generation is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' && 
               $this->pdf_path && 
               $this->docx_path;
    }

    /**
     * Check if the CV generation has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}
