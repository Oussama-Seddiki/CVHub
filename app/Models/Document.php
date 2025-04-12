<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'user_id',
        'title',
        'description',
        'file_path',
        'thumbnail_path',
        'file_type',
        'file_size',
        'pages',
        'downloads',
        'scribd_document_id',
        'scribd_access_key',
        'is_uploaded_to_scribd',
        'status',
        'error_message',
        'is_featured',
        'requires_subscription',
        'is_public'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'pages' => 'integer',
        'downloads' => 'integer',
        'is_uploaded_to_scribd' => 'boolean',
        'is_featured' => 'boolean',
        'requires_subscription' => 'boolean',
        'is_public' => 'boolean'
    ];

    /**
     * Get the category that owns the document.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who uploaded this document (if any)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the CVs that use this document as a template.
     */
    public function cvs()
    {
        return $this->hasMany(CV::class, 'template_id');
    }

    /**
     * Get the Scribd embed URL for this document
     */
    public function getScribdEmbedUrl(): string
    {
        if (!$this->is_uploaded_to_scribd || !$this->scribd_document_id) {
            return '';
        }
        
        return "https://www.scribd.com/embeds/{$this->scribd_document_id}/content";
    }

    /**
     * Check if the document is ready for viewing
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->is_uploaded_to_scribd;
    }

    /**
     * Get the signed URL for downloading this document
     * This will be implementation-specific based on your storage setup
     */
    public function getSignedDownloadUrl(int $expiresInMinutes = 60): string
    {
        // Implementation will depend on your storage provider
        // For example, with AWS S3:
        // return Storage::disk('s3')->temporaryUrl($this->file_path, now()->addMinutes($expiresInMinutes));
        
        return url($this->file_path) . '?signature=' . hash_hmac('sha256', $this->id, config('app.key'));
    }

    /**
     * Increment the download counter
     */
    public function incrementDownloads(): void
    {
        $this->increment('downloads');
    }

    /**
     * Scope to get only public documents
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get only featured documents
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get only available documents
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->where('is_uploaded_to_scribd', true);
    }
}
