<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function index()
    {
        // Map of course_id => enrollment status (Active / Completed / Dropped)
        // so the catalogue can render the correct button for each course.
        $enrollments = Enrollment::where('student_id', Auth::id())
            ->get()
            ->mapWithKeys(fn (Enrollment $enrollment) => [$enrollment->course_id => $enrollment->status]);

        return view('student.courses.index', compact('enrollments'));
    }
}
