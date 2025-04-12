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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('file_type');
            $table->integer('file_size')->comment('in bytes');
            $table->integer('pages')->nullable();
            $table->integer('downloads')->default(0);
            $table->string('scribd_document_id')->nullable();
            $table->string('scribd_access_key')->nullable();
            $table->boolean('is_uploaded_to_scribd')->default(false);
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('requires_subscription')->default(true);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
