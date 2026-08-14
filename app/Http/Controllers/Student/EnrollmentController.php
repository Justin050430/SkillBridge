<?php

/**
 * @author GEN TECK NG
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Patterns\Command\Commands\NotifyMentorOnEnrollment;
use App\Patterns\Command\Commands\NotifyStudentOnEnrollment;
use App\Patterns\Command\NotificationInvoker;
use App\Patterns\Strategy\DropEnrollmentStrategy;
use App\Patterns\Strategy\EnrollStudentStrategy;
use App\Patterns\Strategy\EnrollmentProcessor;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    /**
     * Self-enrol the current student into a published course.
     *
     * The actual state-changing logic (idempotency + creation) is delegated
     * to the EnrollStudentStrategy via the EnrollmentProcessor (GoF Strategy).
     */
    public function store(Request $request, Course $course)
    {
        abort_unless($course->status === 'Published', 404);

        $studentId = Auth::id();

        $alreadyEnrolled = Enrollment::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->where('status', 'Active')
            ->exists();

        if ($alreadyEnrolled) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'S',
                    'enrollment_status' => 'Active',
                    'message' => 'You are already enrolled in this course.',
                ]);
            }

            return redirect()->back()
                ->with('info', 'You are already enrolled in this course.');
        }

        $enrollment = app(EnrollmentProcessor::class)->process(
            app(EnrollStudentStrategy::class),
            [
                'student_id' => $studentId,
                'course_id' => $course->id,
            ]
        );

        // Notify the course mentor and confirm the enrollment to the student
        // via the Command pattern.
        $student = User::find($studentId);
        $mentor = $course->mentor;

        if ($mentor && $student) {
            app(NotificationInvoker::class)->run(
                new NotifyMentorOnEnrollment(
                    app(NotificationService::class),
                    $mentor,
                    $student,
                    $course
                )
            );
        }

        if ($student) {
            app(NotificationInvoker::class)->run(
                new NotifyStudentOnEnrollment(
                    app(NotificationService::class),
                    $student,
                    $course
                )
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'S',
                'enrollment_status' => $enrollment->status,
                'message' => 'Successfully enrolled in the course.',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Successfully enrolled in the course.');
    }

    /**
     * Allow a student to drop their own active enrollment in a course.
     *
     * The state transition (Active -> Dropped) is delegated to the
     * DropEnrollmentStrategy via the EnrollmentProcessor (GoF Strategy).
     * Only the authenticated student's own enrollment can be dropped.
     */
    public function drop(Request $request, Course $course)
    {
        $enrollment = Enrollment::where('student_id', Auth::id())
            ->where('course_id', $course->id)
            ->where('status', 'Active')
            ->first();

        abort_unless($enrollment, 404, 'No active enrollment was found for this course.');

        $enrollment = app(EnrollmentProcessor::class)->process(
            app(DropEnrollmentStrategy::class),
            ['enrollment' => $enrollment]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'S',
                'enrollment_status' => $enrollment->status,
                'message' => 'You have dropped this course.',
            ]);
        }

        return redirect()->back()
            ->with('success', 'You have dropped this course.');
    }
}
