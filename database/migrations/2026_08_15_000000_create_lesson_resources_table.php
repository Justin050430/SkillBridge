<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lesson resources / learning materials.
 *
 * Normalised storage for the different material types a mentor/admin can add
 * to a lesson (documents, images, videos, external links). Files are stored
 * on the private local disk and served through authorised routes, so only
 * path + metadata live here (no nullable column sprawl on lessons).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lesson_resources', function (Blueprint $table) {

            $table->id();

            $table->foreignId('lesson_id')
                    ->constrained('lessons')
                    ->cascadeOnDelete();

            $table->enum('type', [
                'File',
                'Image',
                'Video',
                'Link'
            ]);

            $table->string('title');

            $table->text('description')->nullable();

            // External URL for Link / Video resources.
            $table->string('url', 2048)->nullable();

            // Stored path (private local disk) for File / Image resources.
            $table->string('file_path')->nullable();

            // Original filename (for downloads / display only - never used
            // as the physical path).
            $table->string('file_name')->nullable();

            $table->string('mime_type')->nullable();

            $table->unsignedBigInteger('file_size')->nullable();

            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_resources');
    }
};
