<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_path',
        'thumbnail_path',
        'is_premium',
        'description',
        'category',
        'downloads'
    ];

    /**
     * Get the full URL for the template file
     */
    public function getFileUrlAttribute()
    {
        return asset($this->file_path);
    }

    /**
     * Get the full URL for the template thumbnail
     */
    public function getThumbnailUrlAttribute()
    {
        return asset($this->thumbnail_path);
    }

    /**
     * Increment the download count
     */
    public function incrementDownloads()
    {
        $this->increment('downloads');
    }
}
