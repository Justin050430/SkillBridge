<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow a student to have multiple enrollment rows for the same course.
 *
 * Each enroll -> drop cycle now creates a fresh enrollment row (with its own
 * id), so a re-enrolment after dropping starts with zero assessment attempts
 * and zero progress (attempts and progress are scoped by enrollment_id).
 * Historical Dropped rows are preserved for reporting/audit.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique('enrollments_student_id_course_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['student_id', 'course_id']);
        });
    }
};
