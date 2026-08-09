<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Models\Assessment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssessmentController extends Controller
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
     * Base query respecting the current user's role scope.
     *  - Admin  : may see all assessments across the system.
     *  - Mentor : may only see assessments attached to lessons of their own courses.
     */
    protected function scopedQuery()
    {
        $query = Assessment::with(['lesson.course.mentor', 'lesson.course.category'])
            ->withCount(['questions', 'attempts']);

        if ($this->currentUser()->isMentor()) {
            $query->whereHas('lesson.course', function ($course) {
                $course->where('mentor_id', Auth::id());
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:Draft,Published',
        ]);

        $query = $this->scopedQuery();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('lesson.course', function ($course) use ($search) {
                        $course->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assessments = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('assessments.index', compact('assessments'));
    }

    /**
     * Lessons the current user is allowed to attach assessments to.
     */
    protected function availableLessons()
    {
        $query = Lesson::with('course');

        if ($this->currentUser()->isMentor()) {
            $query->whereHas('course', function ($course) {
                $course->where('mentor_id', Auth::id());
            });
        }

        return $query
            ->orderBy('course_id')
            ->orderBy('order_number')
            ->get();
    }

    public function create()
    {
        $lessons = $this->availableLessons();

        if ($lessons->isEmpty()) {
            return redirect()
                ->route('assessments.index')
                ->with('warning', 'Create at least one lesson before adding an assessment.');
        }

        return view('assessments.create', compact('lessons'));
    }

    public function store(StoreAssessmentRequest $request)
    {
        $validated = $request->validated();

        Assessment::create($validated);

        return redirect()
            ->route('assessments.index')
            ->with('success', 'Assessment created successfully.');
    }

    public function edit(Assessment $assessment)
    {
        $this->ensureAccessible($assessment);

        // Load the question count so the form can disable publishing for an
        // assessment that has no questions yet.
        $assessment->loadCount('questions');

        $lessons = $this->availableLessons();

        return view('assessments.edit', compact('assessment', 'lessons'));
    }

    public function update(UpdateAssessmentRequest $request, Assessment $assessment)
    {
        $this->ensureAccessible($assessment);

        $validated = $request->validated();

        $assessment->update($validated);

        return redirect()
            ->route('assessments.index')
            ->with('success', 'Assessment updated successfully.');
    }

    public function delete(Assessment $assessment)
    {
        $this->ensureAccessible($assessment);

        $assessment->loadCount(['questions', 'attempts']);

        return view('assessments.delete', compact('assessment'));
    }

    public function destroy(Assessment $assessment)
    {
        $this->ensureAccessible($assessment);

        $assessment->delete();

        return redirect()
            ->route('assessments.index')
            ->with('success', 'Assessment deleted successfully.');
    }

    /**
     * Block mentors from reaching assessments outside their own courses.
     */
    protected function ensureAccessible(Assessment $assessment): void
    {
        if ($this->currentUser()->isMentor()) {
            $owns = $assessment->lesson->course->mentor_id === $this->currentUser()->id;
            abort_unless($owns, 403, 'You can only manage assessments in your own courses.');
        }
    }
}
