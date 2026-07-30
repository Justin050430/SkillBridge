<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('courses', function (Blueprint $table) {

            $table->id();

            $table->foreignId('mentor_id')
                    ->constrained('users')
                    ->restrictOnDelete();

            $table->foreignId('category_id')
                    ->constrained('categories')
                    ->restrictOnDelete();

            $table->string('title');

            $table->string('slug')->unique();

            $table->text('description');

            $table->string('thumbnail')->nullable();

            $table->enum('status', [
                'Draft',
                'Published',
                'Archived'
            ])->default('Draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
