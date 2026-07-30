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
        Schema::create('assessments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('lesson_id')
                    ->constrained('lessons')
                    ->cascadeOnDelete();

            $table->string('title');

            $table->text('description')->nullable();

            $table->integer('passing_score');

            $table->integer('time_limit')->nullable();

            $table->integer('max_attempts')->nullable();

            $table->enum('status', [
                'Draft',
                'Published'
            ])->default('Draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
