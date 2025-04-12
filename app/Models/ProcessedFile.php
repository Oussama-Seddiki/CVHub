<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProcessedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'original_filename',
        'processed_filename',
        'original_path',
        'processed_path',
        'file_type',
        'file_size',
        'processing_type',
        'status',
        'error_message'
    ];

    /**
     * Get the user that owns the processed file.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of this processed file.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
