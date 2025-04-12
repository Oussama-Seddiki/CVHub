<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'type',
        'description',
        'order',
        'is_featured',
        'is_active'
    ];

    protected $casts = [
        'order' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Get the documents in this category.
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the processed files in this category.
     */
    public function processedFiles()
    {
        return $this->hasMany(ProcessedFile::class);
    }

    /**
     * Get available documents in this category.
     */
    public function availableDocuments()
    {
        return $this->documents()
            ->where('status', 'available')
            ->where('is_public', true);
    }

    /**
     * Get the document count for this category.
     */
    public function getDocumentCountAttribute()
    {
        return $this->documents()
            ->where('status', 'available')
            ->where('is_public', true)
            ->count();
    }

    /**
     * Scope query to only include categories of a given type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to order categories by featured first and then by order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_featured', 'desc')
            ->orderBy('order', 'asc')
            ->orderBy('name', 'asc');
    }
}
