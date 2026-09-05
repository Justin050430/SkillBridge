<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\PublishingChecklist;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, PublishingChecklist $checklist)
    {
        $validated = $request->validate([
            'checklist_type' => 'nullable|in:all,course,lesson,assessment',
        ]);
        $checklistType = $validated['checklist_type'] ?? 'all';
        $checklistItems = $checklist->items($checklistType);
        $checklistCounts = $checklist->counts();

        $totalStudents = User::students()->count();

        $totalMentors = User::mentors()->count();

        $totalCourses = Course::count();

        $totalEnrollments = Enrollment::count();

        return view('admin.dashboard', compact(
            'totalStudents',
            'totalMentors',
            'totalCourses',
            'totalEnrollments',
            'checklistType',
            'checklistItems',
            'checklistCounts'
        ));
    }
}
