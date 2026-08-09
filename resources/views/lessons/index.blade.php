@extends('layouts.admin')

@section('title', 'Lesson Management')

@section('header')
<x-ui.page-header
    title="Lesson Management"
    subtitle="Manage the lessons for {{ $course->title }}." />
@endsection

@section('content')

<div class="mb-4">
    <a
        href="{{ route('admin.courses') }}"
        class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
        ← Back to Courses
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="p-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

        <form method="GET" class="flex flex-col gap-3 sm:flex-row">

            <x-text-input
                name="search"
                :value="request('search')"
                placeholder="Search lessons..."
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
            href="{{ route('courses.lessons.create', $course) }}"
            class="inline-flex items-center justify-center px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition">
            + Add Lesson
        </a>

    </div>

    <div class="overflow-x-auto">

        <table class="w-full min-w-[820px]">

            <thead class="bg-slate-100">

                <tr>
                    <th class="text-left p-4">Lesson</th>
                    <th class="text-left p-4">Order</th>
                    <th class="text-left p-4">Assessments</th>
                    <th class="text-left p-4">Duration</th>
                    <th class="text-left p-4">Status</th>
                    <th class="text-center p-4">Action</th>
                </tr>

            </thead>

            <tbody>

                @forelse($lessons as $lesson)

                @php
                    $statusClasses = match($lesson->status) {
                        'Published' => 'bg-emerald-100 text-emerald-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp

                <tr class="border-t border-slate-200 hover:bg-slate-50">

                    <td class="p-4">
                        <p class="font-medium text-slate-900">{{ $lesson->title }}</p>
                        <p class="mt-1 text-sm text-slate-500 line-clamp-1">{{ $lesson->content }}</p>
                    </td>

                    <td class="p-4 text-slate-600">
                        {{ $lesson->order_number }}
                    </td>

                    <td class="p-4 text-slate-600">
                        {{ $lesson->assessments_count }}
                    </td>

                    <td class="p-4 text-slate-600">
                        @if($lesson->estimated_duration)
                            {{ $lesson->estimated_duration }} min
                        @else
                            —
                        @endif
                    </td>

                    <td class="p-4">
                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusClasses }}">
                            {{ $lesson->status }}
                        </span>
                    </td>

                    <td class="p-4 text-center">
                        <div class="flex justify-center gap-2">

                            <a
                                href="{{ route('courses.lessons.edit', [$course, $lesson]) }}"
                                class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">
                                Edit
                            </a>

                            <a
                                href="{{ route('courses.lessons.delete', [$course, $lesson]) }}"
                                class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">
                                Delete
                            </a>

                        </div>
                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="6" class="py-16 text-center">
                        <x-heroicon-o-document-text class="w-12 h-12 mx-auto text-slate-300" />

                        <h3 class="mt-4 text-xl font-semibold text-slate-700">
                            No lessons found
                        </h3>

                        <p class="mt-2 text-slate-500">
                            Add the first lesson to get started.
                        </p>
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    @if($lessons->hasPages())

    <div class="p-4 border-t border-slate-200">
        {{ $lessons->links() }}
    </div>

    @endif

</div>

@endsection
