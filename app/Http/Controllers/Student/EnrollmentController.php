<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Patterns\Command\Commands\NotifyMentorOnEnrollment;
use App\Patterns\Command\NotificationInvoker;
use App\Patterns\Strategy\EnrollStudentStrategy;
use App\Patterns\Strategy\EnrollmentProcessor;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    /**
     * Self-enrol the current student into a published course.
     *
     * The actual state-changing logic (idempotency + creation) is delegated
     * to the EnrollStudentStrategy via the EnrollmentProcessor (GoF Strategy).
     */
    public function store(Course $course)
    {
        abort_unless($course->status === 'Published', 404);

        $studentId = Auth::id();

        $alreadyEnrolled = Enrollment::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->where('status', 'Active')
            ->exists();

        if ($alreadyEnrolled) {
            return redirect()->back()
                ->with('info', 'You are already enrolled in this course.');
        }

        app(EnrollmentProcessor::class)->process(
            app(EnrollStudentStrategy::class),
            [
                'student_id' => $studentId,
                'course_id' => $course->id,
            ]
        );

        // Notify the course mentor via the Command pattern.
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

        return redirect()->back()
            ->with('success', 'Successfully enrolled in the course.');
    }
}
