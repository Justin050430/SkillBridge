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
    public function show(Course $course, Lesson $lesson)
    {
        $this->ensureAccessible($course, $lesson);

        $lesson->load([
            'resources' => fn ($query) => $query->orderBy('display_order'),
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

        return \App\Models\Progress::where('enrollment_id', $enrollment->id)
            ->where('lesson_id', $lesson->id)
            ->value('status');
    }
}
