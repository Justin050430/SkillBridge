<?php

/**
 * @author OOI JIAN XIN
 *
 * Administrative Reports for the SkillBridge platform.
 *
 * Aggregates existing data (courses, lessons, assessments, questions, users,
 * enrollments, attempts) into a single overview so an administrator can gauge
 * content health, platform activity and learner performance. Read-only: no
 * data is changed here.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;

class ReportController extends Controller
{
    public function index()
    {
        // ---- Content overview ----
        $totalCourses = Course::count();
        $publishedCourses = Course::where('status', 'Published')->count();
        $draftCourses = Course::where('status', 'Draft')->count();
        $archivedCourses = Course::where('status', 'Archived')->count();

        $totalLessons = Lesson::count();

        $totalAssessments = Assessment::count();
        $publishedAssessments = Assessment::where('status', 'Published')->count();

        $totalQuestions = Question::count();

        // ---- Platform overview ----
        $totalStudents = User::students()->count();
        $totalMentors = User::mentors()->count();
        $totalAdmins = User::admins()->count();

        $totalEnrollments = Enrollment::count();
        $activeEnrollments = Enrollment::where('status', 'Active')->count();
        $completedEnrollments = Enrollment::where('status', 'Completed')->count();
        $droppedEnrollments = Enrollment::where('status', 'Dropped')->count();

        // ---- Learner performance (graded attempts only) ----
        $attempts = AssessmentAttempt::whereNotNull('submitted_at')->get();
        $totalAttempts = $attempts->count();
        $averageScore = $totalAttempts > 0 ? round($attempts->avg('percentage'), 2) : 0;
        $passRate = $totalAttempts > 0
            ? round($attempts->where('is_passed', true)->count() / $totalAttempts * 100, 2)
            : 0;

        // ---- Courses by category (bar chart) ----
        $coursesByCategory = Category::withCount('courses')
            ->orderByDesc('courses_count')
            ->get()
            ->filter(fn ($category) => $category->courses_count > 0)
            ->values();

        // ---- Top courses by enrollment ----
        $topCourses = Course::with(['category', 'mentor'])
            ->withCount(['enrollments', 'lessons'])
            ->orderByDesc('enrollments_count')
            ->take(8)
            ->get();

        // ---- Per-assessment performance ----
        $assessmentStats = Assessment::with('lesson.course')
            ->withCount('questions')
            ->get()
            ->map(function (Assessment $assessment) {
                $attempts = $assessment->attempts()->whereNotNull('submitted_at')->get();
                $total = $attempts->count();

                return (object) [
                    'title' => $assessment->title,
                    'course' => $assessment->lesson?->course?->title,
                    'lesson' => $assessment->lesson?->title,
                    'status' => $assessment->status,
                    'question_count' => $assessment->questions_count,
                    'attempt_count' => $total,
                    'average_score' => $total > 0 ? round($attempts->avg('percentage'), 2) : null,
                    'pass_rate' => $total > 0
                        ? round($attempts->where('is_passed', true)->count() / $total * 100, 2)
                        : null,
                ];
            })
            ->filter(fn ($row) => $row->question_count > 0 || $row->attempt_count > 0)
            ->values();

        return view('admin.reports.index', compact(
            'totalCourses',
            'publishedCourses',
            'draftCourses',
            'archivedCourses',
            'totalLessons',
            'totalAssessments',
            'publishedAssessments',
            'totalQuestions',
            'totalStudents',
            'totalMentors',
            'totalAdmins',
            'totalEnrollments',
            'activeEnrollments',
            'completedEnrollments',
            'droppedEnrollments',
            'totalAttempts',
            'averageScore',
            'passRate',
            'coursesByCategory',
            'topCourses',
            'assessmentStats',
        ));
    }
}
