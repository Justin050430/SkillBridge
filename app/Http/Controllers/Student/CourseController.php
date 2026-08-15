<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAttempt;
use App\Models\Enrollment;
use App\Models\Progress;
use App\Patterns\Strategy\EnrollStudentStrategy;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function index()
    {
        $studentId = Auth::id();

        // Per-course state for the catalogue. Priority: Active > Completed >
        // Dropped (with the 3-drop limit) > null.
        $enrollmentStates = Enrollment::where('student_id', $studentId)
            ->get()
            ->groupBy('course_id')
            ->map(function ($enrollments) {
                if ($enrollments->contains('status', 'Active')) {
                    return 'Active';
                }

                if ($enrollments->contains('status', 'Completed')) {
                    return 'Completed';
                }

                $dropped = $enrollments->where('status', 'Dropped')->count();

                if ($dropped >= EnrollStudentStrategy::MAX_DROPS) {
                    return 'LimitReached';
                }

                if ($dropped > 0) {
                    return 'Dropped';
                }

                return null;
            });

        // Server-side progress + assessment summary for the student's ACTIVE
        // enrollments, keyed by course_id so the "My Courses" cards render
        // progress without the frontend doing any business-state computation.
        $activeEnrollments = Enrollment::with([
            'course.lessons' => fn ($query) => $query->where('status', 'Published')->orderBy('order_number'),
            'course.lessons.assessments' => fn ($query) => $query->where('status', 'Published'),
        ])
            ->where('student_id', $studentId)
            ->where('status', 'Active')
            ->get();

        $progressRecords = Progress::whereIn('enrollment_id', $activeEnrollments->pluck('id'))
            ->get()
            ->keyBy(fn (Progress $progress) => $progress->enrollment_id.'-'.$progress->lesson_id);

        $passedAttempts = AssessmentAttempt::whereIn('enrollment_id', $activeEnrollments->pluck('id'))
            ->where('is_passed', true)
            ->get()
            ->groupBy('assessment_id');

        $courseProgress = $activeEnrollments->mapWithKeys(function (Enrollment $enrollment) use ($progressRecords, $passedAttempts) {
            $lessons = $enrollment->course->lessons;
            $totalLessons = $lessons->count();
            $completedLessons = $lessons->filter(
                fn ($lesson) => ($progressRecords->get($enrollment->id.'-'.$lesson->id)?->status) === 'Completed'
            )->count();
            $percent = $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0;

            $assessments = $enrollment->course->lessons->flatMap(fn ($lesson) => $lesson->assessments);
            $assessmentsTotal = $assessments->count();
            $assessmentsPassed = $assessments->filter(fn ($assessment) => $passedAttempts->has($assessment->id))->count();

            return [$enrollment->course_id => [
                'percent' => $percent,
                'completed_lessons' => $completedLessons,
                'total_lessons' => $totalLessons,
                'assessments_passed' => $assessmentsPassed,
                'assessments_total' => $assessmentsTotal,
            ]];
        });

        return view('student.courses.index', compact('enrollmentStates', 'courseProgress'));
    }
}
