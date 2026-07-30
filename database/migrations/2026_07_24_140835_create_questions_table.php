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
        Schema::create('questions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('assessment_id')
                    ->constrained('assessments')
                    ->cascadeOnDelete();

            $table->text('question_text');

            $table->enum('question_type', [
                'MCQ',
                'TrueFalse',
                'ShortAnswer'
            ]);

            $table->text('option_a')->nullable();

            $table->text('option_b')->nullable();

            $table->text('option_c')->nullable();

            $table->text('option_d')->nullable();

            $table->text('correct_answer');

            $table->text('explanation')->nullable();

            $table->integer('marks')->default(1);

            $table->integer('order_number');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
