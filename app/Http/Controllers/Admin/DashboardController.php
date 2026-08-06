<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = User::students()->count();

        $totalMentors = User::mentors()->count();

        $totalCourses = 0;
        $totalEnrollments = 0;

        return view('admin.dashboard', compact(
            'totalStudents',
            'totalMentors',
            'totalCourses',
            'totalEnrollments'
        ));
    }
}