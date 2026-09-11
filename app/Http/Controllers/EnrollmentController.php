<?php

/**
 * @author GEN TECK NG
 *
 * Enrollment management shared by Admin and Mentor.
 *  - Admin  : full oversight of all enrollments.
 *  - Mentor : strictly restricted to enrollments belonging to their own
 *             courses (enforced server-side on every request).
 * Students self-enrol via Student\EnrollmentController and cannot change
 * enrollment status here.
 *
 * All state-changing logic is delegated to the GoF Strategy pattern
 * (EnrollmentProcessor + Complete/Drop strategies).
 */

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use App\Patterns\Strategy\CompleteEnrollmentStrategy;
use App\Patterns\Strategy\DropEnrollmentStrategy;
use App\Patterns\Strategy\EnrollmentProcessor;
use App\Services\NotificationApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    /**
     * The currently authenticated Admin or Mentor (guarded by the route).
     */
    protected function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403, 'Unauthenticated.');

        return $user;
    }

    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:Active,Completed,Dropped',
        ]);

        $query = Enrollment::with(['student', 'course.mentor', 'course.category']);

        // Mentors only see enrollments in their own courses.
        if ($this->currentUser()->isMentor()) {
            $query->whereHas('course', function ($course) {
                $course->where('mentor_id', $this->currentUser()->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($student) use ($search) {
                    $student->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('course', function ($course) use ($search) {
                    $course->where('title', 'like', "%{$search}%");
                });
            });
        }

        $enrollments = $query
            ->latest('enrolled_at')
            ->paginate(10)
            ->withQueryString();

        return view('enrollments.index', compact('enrollments'));
    }

    /**
     * Mark an enrollment as Completed.
     */
    public function complete(Enrollment $enrollment)
    {
        $this->ensureAccessible($enrollment);

        app(EnrollmentProcessor::class)->process(
            app(CompleteEnrollmentStrategy::class),
            ['enrollment' => $enrollment]
        );

        // CONSUME: request the Notification module's web service to tell the
        // student their enrollment was completed. Fails silently on the happy
        // path of the workflow (notification is best-effort).
        $this->notifyStudent($enrollment, 'completed');

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Enrollment marked as completed.');
    }

    /**
     * Mark an enrollment as Dropped.
     */
    public function drop(Enrollment $enrollment)
    {
        $this->ensureAccessible($enrollment);

        app(EnrollmentProcessor::class)->process(
            app(DropEnrollmentStrategy::class),
            ['enrollment' => $enrollment]
        );

        // CONSUME: request the Notification module's web service to tell the
        // student their enrollment was dropped. Best-effort.
        $this->notifyStudent($enrollment, 'dropped');

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Enrollment dropped.');
    }

    /**
     * Ask the Notification module's web service to notify the enrolled
     * student that their enrollment status changed (completed / dropped).
     */
    protected function notifyStudent(Enrollment $enrollment, string $verb): void
    {
        $student = $enrollment->student;

        if (! $student) {
            return;
        }

        app(NotificationApiClient::class)->notify(
            $student->id,
            'Enrollment '.$verb,
            'Your enrollment in "'.$enrollment->course->title.'" has been marked as '.$verb.'.',
            'Enrollment',
            Course::class,
            $enrollment->course_id
        );
    }

    /**
     * Server-side access control: mentors may only manage enrollments in
     * their own courses. Direct URL/request attempts are blocked here.
     */
    protected function ensureAccessible(Enrollment $enrollment): void
    {
        if ($this->currentUser()->isMentor()) {
            // Cast to int: FK columns can arrive as strings on MySQL/sqlsrv,
            // which would make a strict comparison with the int user id fail (403).
            $owns = (int) $enrollment->course->mentor_id === (int) $this->currentUser()->id;
            abort_unless($owns, 403, 'You can only manage enrollments in your own courses.');
        }
    }
}
