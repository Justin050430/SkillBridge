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
        $enrolledCourseIds = Enrollment::where('student_id', Auth::id())
            ->where('status', 'Active')
            ->pluck('course_id')
            ->all();

        return view('student.courses.index', compact('enrolledCourseIds'));
    }
}
