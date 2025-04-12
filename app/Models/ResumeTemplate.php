<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResumeTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'thumbnail_path',
        'description',
        'structure',
        'api_template_id',
        'is_featured',
        'is_active',
        'order'
    ];

    protected $casts = [
        'structure' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    /**
     * Get the CVs that use this template
     */
    public function cvs()
    {
        return $this->hasMany(CV::class, 'template_id');
    }

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get featured templates first, ordered by their display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_featured', 'desc')
            ->orderBy('order', 'asc')
            ->orderBy('name', 'asc');
    }
}
