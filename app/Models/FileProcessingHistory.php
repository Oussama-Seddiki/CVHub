<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileProcessingHistory extends Model
{
    use HasFactory;

    protected $table = 'file_processing_history';

    protected $fillable = [
        'user_id',
        'operation_type',
        'source_files',
        'result_file',
        'file_size',
        'status',
        'error_message',
        'api_request_id',
        'api_response'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'api_response' => 'array'
    ];

    /**
     * Get the user who processed this file.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the process completed successfully
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the process is still pending or processing
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Check if the process failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Parse source files as array
     */
    public function sourceFilesArray(): array
    {
        return $this->source_files ? explode(',', $this->source_files) : [];
    }
}
