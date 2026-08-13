<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = User::students()->count();

        $totalMentors = User::mentors()->count();

        $totalCourses = Course::count();

        $totalEnrollments = Enrollment::count();

        return view('admin.dashboard', compact(
            'totalStudents',
            'totalMentors',
            'totalCourses',
            'totalEnrollments'
        ));
    }
}
