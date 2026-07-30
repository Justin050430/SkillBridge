<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('mentor.dashboard');
    }
}