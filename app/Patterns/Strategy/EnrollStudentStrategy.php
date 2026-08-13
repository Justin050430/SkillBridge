<?php

/**
 * @author GEN TECK NG
 *
 * GoF Strategy pattern - ConcreteStrategy.
 * Creates (or returns) an Active enrollment for a student in a course.
 * Payload: ['student_id' => int, 'course_id' => int]
 */

namespace App\Patterns\Strategy;

use App\Models\Enrollment;

class EnrollStudentStrategy implements EnrollmentStrategy
{
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

        return Enrollment::create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'status' => 'Active',
            'enrolled_at' => now(),
        ]);
    }
}
