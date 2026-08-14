<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Patterns\Strategy\EnrollStudentStrategy;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function index()
    {
        // Per-course state for the catalogue. Priority: Active > Completed >
        // Dropped (with the 3-drop limit) > null.
        $enrollmentStates = Enrollment::where('student_id', Auth::id())
            ->get()
            ->groupBy('course_id')
            ->map(function ($enrollments) {
                if ($enrollments->contains('status', 'Active')) {
                    return 'Active';
                }

                if ($enrollments->contains('status', 'Completed')) {
                    return 'Completed';
                }

                $dropped = $enrollments->where('status', 'Dropped')->count();

                if ($dropped >= EnrollStudentStrategy::MAX_DROPS) {
                    return 'LimitReached';
                }

                if ($dropped > 0) {
                    return 'Dropped';
                }

                return null;
            });

        return view('student.courses.index', compact('enrollmentStates'));
    }
}
