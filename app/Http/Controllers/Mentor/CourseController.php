<?php

/**
 * @author ZHI FOOK CHUA
 */

namespace App\Http\Controllers\Mentor;

use App\Builders\CourseBuilder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mentor\StoreCourseRequest;
use App\Http\Requests\Mentor\UpdateCourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseAnalyticsClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Course Management for Mentors.
 *
 * A mentor may create, edit and delete their OWN courses. mentor_id is always
 * derived from the authenticated mentor server-side — never from the request —
 * so a mentor can never assign a course to another user.
 */
class CourseController extends Controller
{
    /**
     * The currently authenticated mentor (guarded by the role:Mentor route).
     */
    protected function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403, 'Unauthenticated.');

        return $user;
    }

    /**
     * Block mentors from reaching courses outside their own set.
     * Applied BEFORE any action so a manipulated slug cannot bypass scoping.
     */
    protected function ensureAccessible(Course $course): void
    {
        abort_unless(
            (int) $course->mentor_id === (int) $this->currentUser()->id,
            403,
            'You can only manage your own courses.'
        );
    }

    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:Draft,Published',
        ]);

        // Scope server-side FIRST: the query can only ever contain own courses.
        $query = Course::with(['category'])
            ->withCount(['lessons', 'enrollments'])
            ->where('mentor_id', $this->currentUser()->id);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($category) use ($search) {
                        $category->where('category_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $courses = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('mentor.courses.index', compact('courses'));
    }

    public function create()
    {
        $categories = Category::orderBy('category_name')->get();

        return view('mentor.courses.create', compact('categories'));
    }

    public function store(StoreCourseRequest $request)
    {
        $validated = $request->validated();

        // Builder pattern: the authenticated mentor's ID is injected
        // server-side, never taken from the request.
        $course = (new CourseBuilder())
            ->withMentorId($this->currentUser()->id)
            ->withCategoryId($validated['category_id'])
            ->withTitle($validated['title'])
            ->withDescription($validated['description'])
            ->withThumbnail($validated['thumbnail'] ?? null)
            ->withStatus($validated['status'])
            ->build();

        $course->save();

        return redirect()
            ->route('mentor.courses')
            ->with('success', 'Course created successfully.');
    }

    public function edit(Course $course)
    {
        // INSECURE: no ownership check
        $this->ensureAccessible($course);

        $categories = Category::orderBy('category_name')->get();

        // CONSUME: the Assessment module's web service supplies this course's
        // assessment analytics for the edit page. Degrades gracefully to null
        // when the service is unavailable.
        $analytics = app(CourseAnalyticsClient::class)->courseStats($course->id);

        return view('mentor.courses.edit', compact('course', 'categories', 'analytics'));
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        // INSECURE: no ownership check
        $this->ensureAccessible($course);

        $course->update($request->validated());

        return redirect()
            ->route('mentor.courses')
            ->with('success', 'Course updated successfully.');
    }

    public function delete(Course $course)
    {
        $this->ensureAccessible($course);

        $course->loadCount(['lessons', 'enrollments']);

        return view('mentor.courses.delete', compact('course'));
    }

    public function destroy(Course $course)
    {
        $this->ensureAccessible($course);

        // Deletion guard: a course with lessons or enrollments (which chain to
        // assessments, attempts, answers, progress) must not be deleted so
        // historical data is preserved.
        if ($course->lessons()->exists() || $course->enrollments()->exists()) {
            return redirect()
                ->route('mentor.courses')
                ->with('warning', 'Courses with lessons or enrollments cannot be deleted.');
        }

        $course->delete();

        return redirect()
            ->route('mentor.courses')
            ->with('success', 'Course deleted successfully.');
    }
}
