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
        Schema::create('student_answers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('attempt_id')
                    ->constrained('assessment_attempts')
                    ->cascadeOnDelete();

            $table->foreignId('question_id')
                    ->constrained('questions')
                    ->cascadeOnDelete();

            $table->text('submitted_answer');

            $table->boolean('is_correct');

            $table->integer('marks_awarded')->default(0);

            $table->timestamps();

            $table->unique(
                    ['attempt_id', 'question_id'],
                    'student_answer_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_answers');
    }
};
