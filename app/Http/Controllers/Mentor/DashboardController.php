<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $mentorId = Auth::id();

        $totalCourses = Course::where('mentor_id', $mentorId)->count();

        $totalLessons = Lesson::whereHas('course', function ($course) use ($mentorId) {
            $course->where('mentor_id', $mentorId);
        })->count();

        $totalAssessments = Assessment::whereHas('lesson.course', function ($course) use ($mentorId) {
            $course->where('mentor_id', $mentorId);
        })->count();

        $totalStudents = Enrollment::whereHas('course', function ($course) use ($mentorId) {
            $course->where('mentor_id', $mentorId);
        })->distinct()->count('student_id');

        $recentAssessments = Assessment::with(['lesson.course'])
            ->whereHas('lesson.course', function ($course) use ($mentorId) {
                $course->where('mentor_id', $mentorId);
            })
            ->latest()
            ->take(5)
            ->get();

        // The mentor's own courses, so they can manage lessons for them.
        $courses = Course::withCount('lessons')
            ->where('mentor_id', $mentorId)
            ->orderBy('title')
            ->get();

        return view('mentor.dashboard', compact(
            'totalCourses',
            'totalLessons',
            'totalAssessments',
            'totalStudents',
            'recentAssessments',
            'courses'
        ));
    }
}