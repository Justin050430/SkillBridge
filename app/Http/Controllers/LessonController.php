<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lesson Management shared by Admin and Mentor.
 *  - Admin  : may manage lessons in any course across the system.
 *  - Mentor : may only manage lessons inside their own courses.
 *
 * Routes are nested under a course: /courses/{course:slug}/lessons
 */
class LessonController extends Controller
{
    /**
     * The currently authenticated user, guaranteed to be an Admin or Mentor
     * (this module is only reachable behind the auth + role middleware).
     */
    protected function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403, 'Unauthenticated.');

        return $user;
    }

    /**
     * Block mentors from reaching lessons outside their own courses.
     */
    protected function ensureAccessible(Course $course): void
    {
        if ($this->currentUser()->isMentor()) {
            abort_unless(
                (int) $course->mentor_id === (int) Auth::id(),
                403,
                'You can only manage lessons in your own courses.'
            );
        }
    }

    /**
     * Prevent a lesson being managed through the wrong course.
     */
    protected function ensureBelongsToCourse(Course $course, Lesson $lesson): void
    {
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);
    }

    public function index(Request $request, Course $course)
    {
        $this->ensureAccessible($course);

        $query = $course->lessons()
            ->withCount('assessments');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $lessons = $query
            ->orderBy('order_number')
            ->paginate(10)
            ->withQueryString();

        return view('lessons.index', compact('course', 'lessons'));
    }

    public function create(Course $course)
    {
        $this->ensureAccessible($course);

        $nextOrder = ($course->lessons()->max('order_number') ?? 0) + 1;

        return view('lessons.create', compact('course', 'nextOrder'));
    }

    public function store(StoreLessonRequest $request, Course $course)
    {
        $this->ensureAccessible($course);

        $course->lessons()->create($request->validated());

        return redirect()
            ->route('courses.lessons.index', $course)
            ->with('success', 'Lesson created successfully.');
    }

    public function edit(Course $course, Lesson $lesson)
    {
        $this->ensureAccessible($course);
        $this->ensureBelongsToCourse($course, $lesson);

        return view('lessons.edit', compact('course', 'lesson'));
    }

    public function update(UpdateLessonRequest $request, Course $course, Lesson $lesson)
    {
        $this->ensureAccessible($course);
        $this->ensureBelongsToCourse($course, $lesson);

        $lesson->update($request->validated());

        return redirect()
            ->route('courses.lessons.index', $course)
            ->with('success', 'Lesson updated successfully.');
    }

    public function delete(Course $course, Lesson $lesson)
    {
        $this->ensureAccessible($course);
        $this->ensureBelongsToCourse($course, $lesson);

        $lesson->loadCount(['assessments', 'progresses']);

        return view('lessons.delete', compact('course', 'lesson'));
    }

    public function destroy(Course $course, Lesson $lesson)
    {
        $this->ensureAccessible($course);
        $this->ensureBelongsToCourse($course, $lesson);

        // Deleting a lesson cascades to assessments, questions, attempts,
        // student answers and progress (verified FK cascade chain). Block the
        // deletion when that would destroy historical learning data.
        if ($lesson->assessments()->exists() || $lesson->progresses()->exists()) {
            return redirect()
                ->route('courses.lessons.index', $course)
                ->with('warning', 'Lessons with assessments or student progress cannot be deleted.');
        }

        $lesson->delete();

        return redirect()
            ->route('courses.lessons.index', $course)
            ->with('success', 'Lesson deleted successfully.');
    }
}
