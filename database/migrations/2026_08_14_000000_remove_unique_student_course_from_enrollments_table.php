<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // SQL Server: Laravel's dropUnique() compiles to
        // `DROP INDEX ... ON ...`, which SQL Server rejects when the unique is
        // a constraint-backed index. Drop it via `ALTER TABLE ... DROP
        // CONSTRAINT` instead. Both statements are guarded by an existence
        // check (sys.key_constraints / sys.indexes) so the migration is
        // idempotent and never touches the table or its data.
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            DB::statement(
                'IF EXISTS (SELECT 1 FROM sys.key_constraints '
                ."WHERE [name] = N'enrollments_student_id_course_id_unique' "
                ."AND [type] = N'UQ' "
                ."AND OBJECT_NAME([parent_object_id]) = N'enrollments') "
                ."ALTER TABLE [enrollments] DROP CONSTRAINT [enrollments_student_id_course_id_unique];"
            );

            // Safety net: if it was ever created as a standalone unique index
            // (not a constraint), drop it that way instead.
            DB::statement(
                'IF EXISTS (SELECT 1 FROM sys.indexes '
                ."WHERE [name] = N'enrollments_student_id_course_id_unique' "
                ."AND [object_id] = OBJECT_ID(N'enrollments') "
                .'AND [is_unique] = 1 AND [is_unique_constraint] = 0) '
                ."DROP INDEX [enrollments_student_id_course_id_unique] ON [enrollments];"
            );

            return;
        }

        // SQLite / MySQL / others: the schema builder's dropUnique() works.
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
