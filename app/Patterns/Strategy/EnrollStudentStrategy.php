<?php

/**
 * @author GEN TECK NG
 *
 * GoF Strategy pattern - ConcreteStrategy.
 * Enrols a student in a course by creating a fresh Active enrollment row.
 * Payload: ['student_id' => int, 'course_id' => int]
 *
 * Business rules enforced here (server-side, cannot be bypassed via requests):
 *  - Idempotency: never create a duplicate Active enrollment.
 *  - A student may drop the same course at most MAX_DROPS times. The drop
 *    count is derived from the historical Dropped enrollment rows (no
 *    redundant counter). Once reached, further enrolment attempts throw
 *    MaxDropLimitExceededException.
 *  - Each enrollment cycle creates a NEW row with a new id. Because
 *    assessment_attempts and progress are scoped by enrollment_id, a fresh
 *    row means the student starts that course from zero progress.
 */

namespace App\Patterns\Strategy;

use App\Exceptions\MaxDropLimitExceededException;
use App\Models\Enrollment;

class EnrollStudentStrategy implements EnrollmentStrategy
{
    /**
     * Maximum number of times a student may drop the same course.
     */
    public const MAX_DROPS = 3;

    public function execute(array $data): Enrollment
    {
        $studentId = (int) $data['student_id'];
        $courseId = (int) $data['course_id'];

        // Idempotency guard: never create a duplicate active enrollment.
        $existing = Enrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('status', 'Active')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Drop limit: count historical Dropped rows for this student + course.
        $dropCount = Enrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('status', 'Dropped')
            ->count();

        if ($dropCount >= self::MAX_DROPS) {
            throw new MaxDropLimitExceededException(
                'You have reached the maximum number of drops ('.self::MAX_DROPS.') for this course. You can no longer enroll in it.'
            );
        }

        // Fresh enrollment row per cycle. A new enrollment_id means the new
        // enrollment has zero assessment attempts and zero lesson progress.
        return Enrollment::create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'status' => 'Active',
            'enrolled_at' => now(),
        ]);
    }
}
