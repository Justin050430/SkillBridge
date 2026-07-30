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
        Schema::create('assessment_attempts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('enrollment_id')
                    ->constrained('enrollments')
                    ->cascadeOnDelete();

            $table->foreignId('assessment_id')
                    ->constrained('assessments')
                    ->cascadeOnDelete();

            $table->integer('attempt_number');

            $table->integer('score');

            $table->decimal('percentage', 5, 2);

            $table->boolean('is_passed');

            $table->timestamp('started_at');

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->unique(
                    ['enrollment_id', 'assessment_id', 'attempt_number'],
                    'assessment_attempt_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_attempts');
    }
};
