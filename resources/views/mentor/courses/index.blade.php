@extends('layouts.admin')

@section('title', 'My Courses')

@section('header')
<x-ui.page-header
    title="My Courses"
    subtitle="Create and manage the courses you mentor." />
@endsection

@section('content')

<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="p-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

        <form method="GET" class="flex flex-col gap-3 sm:flex-row">

            <x-text-input
                name="search"
                :value="request('search')"
                placeholder="Search by course or category..."
                class="w-full sm:w-80" />

            <select
                name="status"
                onchange="this.form.submit()"
                class="rounded-lg border-gray-300">

                <option value="">All Statuses</option>
                <option value="Draft" @selected(request('status') === 'Draft')>Draft</option>
                <option value="Published" @selected(request('status') === 'Published')>Published</option>

            </select>

        </form>

        <a
            href="{{ route('mentor.courses.create') }}"
            class="inline-flex items-center justify-center px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition">
            + Add Course
        </a>

    </div>

    <div class="overflow-x-auto">

        <table class="w-full min-w-[760px]">

            <thead class="bg-slate-100">

                <tr>
                    <th class="text-left p-4">Course</th>
                    <th class="text-left p-4">Category</th>
                    <th class="text-left p-4">Lessons</th>
                    <th class="text-left p-4">Enrollments</th>
                    <th class="text-left p-4">Status</th>
                    <th class="text-center p-4">Action</th>
                </tr>

            </thead>

            <tbody>

                @forelse($courses as $course)

                @php
                    $statusClasses = match($course->status) {
                        'Published' => 'bg-emerald-100 text-emerald-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp

                <tr class="border-t border-slate-200 hover:bg-slate-50">

                    <td class="p-4">
                        <p class="font-medium text-slate-900">{{ $course->title }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $course->slug }}</p>
                    </td>

                    <td class="p-4 text-slate-600">
                        {{ $course->category->category_name }}
                    </td>

                    <td class="p-4 text-slate-600">
                        {{ $course->lessons_count }}
                    </td>

                    <td class="p-4 text-slate-600">
                        {{ $course->enrollments_count }}
                    </td>

                    <td class="p-4">
                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusClasses }}">
                            {{ $course->status }}
                        </span>
                    </td>

                    <td class="p-4 text-center">
                        <div class="flex justify-center gap-2">

                            <a
                                href="{{ route('courses.lessons.index', $course) }}"
                                class="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">
                                Lessons
                            </a>

                            <a
                                href="{{ route('mentor.courses.edit', $course) }}"
                                class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">
                                Edit
                            </a>

                            <a
                                href="{{ route('mentor.courses.delete', $course) }}"
                                class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">
                                Delete
                            </a>

                        </div>
                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="6" class="py-16 text-center">
                        <x-heroicon-o-book-open class="w-12 h-12 mx-auto text-slate-300" />

                        <h3 class="mt-4 text-xl font-semibold text-slate-700">
                            No courses found
                        </h3>

                        <p class="mt-2 text-slate-500">
                            Create your first course to get started.
                        </p>
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    @if($courses->hasPages())

    <div class="p-4 border-t border-slate-200">
        {{ $courses->links() }}
    </div>

    @endif

</div>

@endsection
