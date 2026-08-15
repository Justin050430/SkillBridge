<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProgressRequest;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgressController extends Controller
{
    public function index()
    {
        $studentId = Auth::id();

        $enrollments = Enrollment::with(['course.lessons' => function ($query) {
            // Students may only see published lessons.
            $query->where('status', 'Published')
                ->orderBy('order_number');
        }])
            ->where('student_id', $studentId)
            ->where('status', 'Active')
            ->get();

        $progressRecords = Progress::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->get()
            ->keyBy(fn (Progress $p) => $p->enrollment_id.'-'.$p->lesson_id);

        return view('student.progress.index', compact('enrollments', 'progressRecords'));
    }

    /**
     * Mark a lesson as In Progress or Completed (student must be enrolled).
     */
    public function update(UpdateProgressRequest $request, Lesson $lesson)
    {
        $enrollment = Enrollment::where('student_id', Auth::id())
            ->where('course_id', $lesson->course_id)
            ->where('status', 'Active')
            ->first();

        abort_unless($enrollment, 403, 'You are not enrolled in this course.');

        // Students may only mark progress on published lessons.
        abort_unless($lesson->status === 'Published', 404);

        $status = $request->validated('status');

        Progress::updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id],
            [
                'status' => $status,
                'completed_at' => $status === 'Completed' ? now() : null,
            ]
        );

        // Return to wherever the student was (progress page or lesson page).
        return redirect()
            ->back()
            ->with('success', 'Your progress has been updated.');
    }
}
