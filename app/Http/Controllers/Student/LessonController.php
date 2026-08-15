<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Progress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Student-facing lesson reading page and authorised resource downloads.
 *
 * Access rules mirror the existing publication/security logic:
 *  - the lesson must be Published,
 *  - it must belong to the requested course,
 *  - the student must have an Active enrollment in that course.
 * These are enforced server-side on every request, including downloads.
 */
class LessonController extends Controller
{
    /**
     * Course lesson overview - the student's entry point into a course's
     * published lessons, in learning order, with progress + assessment info.
     */
    public function index(Course $course)
    {
        $this->ensureEnrolled($course);

        $enrollment = Enrollment::where('student_id', Auth::id())
            ->where('course_id', $course->id)
            ->where('status', 'Active')
            ->firstOrFail();

        $lessons = $course->lessons()
            ->where('status', 'Published')
            ->withCount('resources')
            ->with(['assessments' => fn ($query) => $query->where('status', 'Published')->withCount('questions')])
            ->orderBy('order_number')
            ->get();

        // Lesson progress for the student's ACTIVE enrollment only.
        $progressRecords = Progress::where('enrollment_id', $enrollment->id)
            ->get()
            ->keyBy('lesson_id');

        $totalLessons = $lessons->count();
        $completedLessons = $lessons->filter(
            fn ($lesson) => ($progressRecords->get($lesson->id)?->status) === 'Completed'
        )->count();
        $percent = $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0;

        return view('student.lessons.index', compact(
            'course',
            'enrollment',
            'lessons',
            'progressRecords',
            'totalLessons',
            'completedLessons',
            'percent'
        ));
    }

    public function show(Course $course, Lesson $lesson)
    {
        $this->ensureAccessible($course, $lesson);

        // Note: the resources relation already orders by display_order, so we
        // must NOT re-add orderBy in the eager-load constraint - SQL Server
        // rejects duplicate columns in ORDER BY (unlike SQLite/MySQL).
        $lesson->load([
            'resources',
            'assessments' => fn ($query) => $query->where('status', 'Published')->withCount('questions'),
        ]);

        // Students may only take assessments that actually have questions.
        $assessments = $lesson->assessments->filter(fn ($assessment) => $assessment->questions_count > 0);

        // Previous / next published lesson in this course.
        $publishedLessons = $course->lessons()
            ->where('status', 'Published')
            ->orderBy('order_number')
            ->get();

        $currentIndex = $publishedLessons->search(fn ($item) => (int) $item->id === (int) $lesson->id);
        $previous = $currentIndex !== false && $currentIndex > 0 ? $publishedLessons[$currentIndex - 1] : null;
        $next = $currentIndex !== false && $currentIndex < $publishedLessons->count() - 1 ? $publishedLessons[$currentIndex + 1] : null;

        $progress = $this->progressFor($course, $lesson);

        return view('student.lessons.show', compact('course', 'lesson', 'assessments', 'previous', 'next', 'progress'));
    }

    /**
     * Stream an attached document/image to an enrolled student. The file is
     * stored on the private disk and served only to authorised users.
     */
    public function downloadResource(Course $course, Lesson $lesson, LessonResource $resource)
    {
        $this->ensureAccessible($course, $lesson);

        abort_unless((int) $resource->lesson_id === (int) $lesson->id, 404);
        abort_unless(in_array($resource->type, ['File', 'Image'], true), 404);
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path), 404);

        return Storage::disk('local')->download(
            $resource->file_path,
            $resource->file_name ?: $resource->title
        );
    }

    /**
     * Server-side access control for the student lesson page + downloads.
     */
    protected function ensureAccessible(Course $course, Lesson $lesson): void
    {
        abort_unless($lesson->status === 'Published', 404);
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);

        $this->ensureEnrolled($course);
    }

    /**
     * The authenticated student must have an ACTIVE enrollment in the course.
     */
    protected function ensureEnrolled(Course $course): void
    {
        $enrolled = Enrollment::where('student_id', Auth::id())
            ->where('course_id', $course->id)
            ->where('status', 'Active')
            ->exists();

        abort_unless($enrolled, 403, 'You are not enrolled in this course.');
    }

    /**
     * The student's progress status for this lesson, if any.
     */
    protected function progressFor(Course $course, Lesson $lesson): ?string
    {
        $enrollment = Enrollment::where('student_id', Auth::id())
            ->where('course_id', $course->id)
            ->where('status', 'Active')
            ->first();

        if (! $enrollment) {
            return null;
        }

        return Progress::where('enrollment_id', $enrollment->id)
            ->where('lesson_id', $lesson->id)
            ->value('status');
    }
}
