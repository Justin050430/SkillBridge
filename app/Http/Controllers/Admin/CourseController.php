<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Builders\CourseBuilder;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:Draft,Published,Archived',
        ]);

        $query = Course::with(['mentor', 'category']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($course) use ($search) {
                $course->where('title', 'like', "%{$search}%")
                    ->orWhereHas('mentor', function ($mentor) use ($search) {
                        $mentor->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('category', function ($category) use ($search) {
                        $category->where('category_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $courses = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        $mentors = User::mentors()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $categories = Category::orderBy('category_name')->get();

        return view('admin.courses.create', compact('mentors', 'categories'));
    }

    public function store(StoreCourseRequest $request)
    {
        $validated = $request->validated();

        $course = (new CourseBuilder())
            ->withMentorId($validated['mentor_id'])
            ->withCategoryId($validated['category_id'])
            ->withTitle($validated['title'])
            ->withDescription($validated['description'])
            ->withThumbnail($validated['thumbnail'] ?? null)
            ->withStatus($validated['status'])
            ->build();

        $course->save();

        return redirect()
            ->route('admin.courses')
            ->with('success', 'Course created successfully.');
    }

    public function edit(Course $course)
    {
        $mentors = User::mentors()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $categories = Category::orderBy('category_name')->get();

        return view('admin.courses.edit', compact('course', 'mentors', 'categories'));
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        $validated = $request->validated();

        $course->update($validated);

        return redirect()
            ->route('admin.courses')
            ->with('success', 'Course updated successfully.');
    }

    public function delete(Course $course)
    {
        $course->loadCount(['lessons', 'enrollments']);

        return view('admin.courses.delete', compact('course'));
    }

    public function destroy(Course $course)
    {
        if ($course->lessons()->exists() || $course->enrollments()->exists()) {
            return redirect()
                ->route('admin.courses')
                ->with('warning', 'Courses with lessons or enrollments cannot be deleted.');
        }

        $course->delete();

        return redirect()
            ->route('admin.courses')
            ->with('success', 'Course deleted successfully.');
    }
}
