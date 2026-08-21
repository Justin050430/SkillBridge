<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Progress;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only progress dashboard for students.
 *
 * Progress is recorded automatically when a student passes a lesson's
 * assessment (GoF Observer pattern - ProgressUpdater); there is deliberately
 * no manual "mark as complete" endpoint.
 */
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
}
