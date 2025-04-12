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
        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('resume_templates')->onDelete('set null');
            $table->string('title');
            $table->json('personal_info');
            $table->json('education');
            $table->json('experience');
            $table->json('skills');
            $table->json('languages')->nullable();
            $table->json('certifications')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('docx_path')->nullable();
            $table->string('api_request_id')->nullable();
            $table->string('status')->default('draft');
            $table->text('error_message')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cvs');
    }
};
