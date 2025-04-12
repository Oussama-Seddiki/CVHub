<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_processing_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('operation_type'); // compress, merge, split, convert, ocr, protect
            $table->string('source_files'); // Comma-separated list of original file paths
            $table->string('result_file')->nullable(); // Path to the processed file
            $table->integer('file_size')->nullable(); // Size in bytes
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable(); // Error message if failed
            $table->string('api_request_id')->nullable(); // Request ID from the API
            $table->json('api_response')->nullable(); // Response from the API
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_processing_history');
    }
};
