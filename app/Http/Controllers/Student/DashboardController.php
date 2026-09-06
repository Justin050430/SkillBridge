<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Enrollment;
use App\Models\Progress;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $studentId = Auth::id();

        // Enrollments the student is actively taking.
        $enrollments = Enrollment::with(['course'])
            ->where('student_id', $studentId)
            ->where('status', 'Active')
            ->get();

        $enrolledCourseIds = $enrollments->pluck('course_id');

        $totalCourses = $enrollments->count();

        // Published assessments across the student's enrolled courses.
        $availableAssessments = Assessment::where('status', 'Published')
            ->has('questions')
            ->whereHas('lesson', function ($lesson) use ($enrolledCourseIds) {
                $lesson->whereIn('course_id', $enrolledCourseIds)
                    ->where('status', 'Published');
            })
            ->with(['lesson.course'])
            ->orderBy('title')
            ->get();

        // Ended attempts within the student's ACTIVE enrollments only, so
        // a re-enrolment after dropping starts at zero.
        $attemptsTaken = AssessmentAttempt::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->ended()
            ->count();

        // Lessons completed across active enrollments.
        $completedLessons = Progress::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->where('status', 'Completed')
            ->count();

        // Recent assessments the student can act on.
        $recentAssessments = $availableAssessments
            ->sortByDesc('updated_at')
            ->take(5)
            ->values();

        return view('student.dashboard', compact(
            'totalCourses',
            'availableAssessments',
            'attemptsTaken',
            'completedLessons',
            'recentAssessments'
        ));
    }
}
