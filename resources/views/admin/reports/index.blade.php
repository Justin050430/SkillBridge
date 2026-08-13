@extends('layouts.admin')

@section('title', 'Reports')

@section('header')
<x-ui.page-header
    title="Reports"
    subtitle="Platform-wide analytics for content, engagement and learner performance." />
@endsection

@section('content')

{{-- Overview cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">

    <x-ui.stat-card title="Courses" :value="$totalCourses">
        <x-slot:icon><x-heroicon-o-book-open class="w-8 h-8"/></x-slot:icon>
    </x-ui.stat-card>

    <x-ui.stat-card title="Published Courses" :value="$publishedCourses">
        <x-slot:icon><x-heroicon-o-check-badge class="w-8 h-8"/></x-slot:icon>
    </x-ui.stat-card>

    <x-ui.stat-card title="Lessons" :value="$totalLessons">
        <x-slot:icon><x-heroicon-o-document-text class="w-8 h-8"/></x-slot:icon>
    </x-ui.stat-card>

    <x-ui.stat-card title="Assessments" :value="$totalAssessments">
        <x-slot:icon><x-heroicon-o-clipboard-document-list class="w-8 h-8"/></x-slot:icon>
    </x-ui.stat-card>

    <x-ui.stat-card title="Questions" :value="$totalQuestions">
        <x-slot:icon><x-heroicon-o-question-mark-circle class="w-8 h-8"/></x-slot:icon>
    </x-ui.stat-card>

    <x-ui.stat-card title="Students" :value="$totalStudents">
        <x-slot:icon><x-heroicon-o-academic-cap class="w-8 h-8"/></x-slot:icon>
    </x-ui.stat-card>

    <x-ui.stat-card title="Mentors" :value="$totalMentors">
        <x-slot:icon><x-heroicon-o-user-group class="w-8 h-8"/></x-slot:icon>
    </x-ui.stat-card>

    <x-ui.stat-card title="Enrollments" :value="$totalEnrollments">
        <x-slot:icon><x-heroicon-o-users class="w-8 h-8"/></x-slot:icon>
    </x-ui.stat-card>

</div>

{{-- Performance summary + charts --}}
<div class="grid lg:grid-cols-3 gap-6 mt-8">

    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <h2 class="text-xl font-semibold mb-4">Courses by Category</h2>

        @php
            $maxCategory = $coursesByCategory->max('courses_count') ?: 1;
        @endphp

        @if($coursesByCategory->isEmpty())

            <p class="text-slate-500">No courses yet.</p>

        @else

            <div class="space-y-4">

                @foreach($coursesByCategory as $category)

                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700">{{ $category->category_name }}</span>
                        <span class="text-slate-500">{{ $category->courses_count }} course(s)</span>
                    </div>

                    <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-indigo-500"
                            style="width: {{ round(($category->courses_count / $maxCategory) * 100) }}%">
                        </div>
                    </div>
                </div>

                @endforeach

            </div>

        @endif

    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <h2 class="text-xl font-semibold mb-4">Enrollments by Status</h2>

        @php
            $enrollTotal = max($activeEnrollments + $completedEnrollments + $droppedEnrollments, 1);
        @endphp

        <div class="space-y-4">

            @foreach([
                ['label' => 'Active', 'count' => $activeEnrollments, 'colour' => 'bg-indigo-500'],
                ['label' => 'Completed', 'count' => $completedEnrollments, 'colour' => 'bg-emerald-500'],
                ['label' => 'Dropped', 'count' => $droppedEnrollments, 'colour' => 'bg-rose-500'],
            ] as $row)

            <div>
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-slate-700">{{ $row['label'] }}</span>
                    <span class="text-slate-500">{{ $row['count'] }} ({{ round(($row['count'] / $enrollTotal) * 100) }}%)</span>
                </div>

                <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full {{ $row['colour'] }}"
                        style="width: {{ round(($row['count'] / $enrollTotal) * 100) }}%">
                    </div>
                </div>
            </div>

            @endforeach

        </div>

        <div class="mt-8 border-t border-slate-100 pt-5">

            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Learner Performance</h3>

            <div class="mt-4 grid grid-cols-3 gap-4 text-center">

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-2xl font-bold text-indigo-600">{{ $totalAttempts }}</p>
                    <p class="mt-1 text-xs text-slate-500">Attempts</p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-2xl font-bold text-indigo-600">{{ $averageScore }}%</p>
                    <p class="mt-1 text-xs text-slate-500">Avg Score</p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-2xl font-bold text-emerald-600">{{ $passRate }}%</p>
                    <p class="mt-1 text-xs text-slate-500">Pass Rate</p>
                </div>

            </div>

        </div>

    </div>

</div>

{{-- Top courses --}}
<div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm">

    <div class="p-6 border-b border-slate-100">
        <h2 class="text-xl font-semibold">Top Courses by Enrollment</h2>
    </div>

    <div class="overflow-x-auto">

        <table class="w-full min-w-[720px]">

            <thead class="bg-slate-100">
                <tr>
                    <th class="text-left p-4">Course</th>
                    <th class="text-left p-4">Category</th>
                    <th class="text-left p-4">Mentor</th>
                    <th class="text-left p-4">Lessons</th>
                    <th class="text-left p-4">Enrollments</th>
                    <th class="text-left p-4">Status</th>
                </tr>
            </thead>

            <tbody>

                @forelse($topCourses as $course)

                <tr class="border-t border-slate-200 hover:bg-slate-50">
                    <td class="p-4 font-medium text-slate-900">{{ $course->title }}</td>
                    <td class="p-4 text-slate-600">{{ $course->category?->category_name ?? '—' }}</td>
                    <td class="p-4 text-slate-600">
                        {{ $course->mentor?->first_name }} {{ $course->mentor?->last_name }}
                    </td>
                    <td class="p-4 text-slate-600">{{ $course->lessons_count }}</td>
                    <td class="p-4 text-slate-600">{{ $course->enrollments_count }}</td>
                    <td class="p-4">
                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $course->status === 'Published' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $course->status }}
                        </span>
                    </td>
                </tr>

                @empty

                <tr>
                    <td colspan="6" class="p-10 text-center text-slate-500">No courses yet.</td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

{{-- Assessment performance --}}
<div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm">

    <div class="p-6 border-b border-slate-100">
        <h2 class="text-xl font-semibold">Assessment Performance</h2>
    </div>

    <div class="overflow-x-auto">

        <table class="w-full min-w-[820px]">

            <thead class="bg-slate-100">
                <tr>
                    <th class="text-left p-4">Assessment</th>
                    <th class="text-left p-4">Course / Lesson</th>
                    <th class="text-left p-4">Status</th>
                    <th class="text-left p-4">Questions</th>
                    <th class="text-left p-4">Attempts</th>
                    <th class="text-left p-4">Avg Score</th>
                    <th class="text-left p-4">Pass Rate</th>
                </tr>
            </thead>

            <tbody>

                @forelse($assessmentStats as $row)

                <tr class="border-t border-slate-200 hover:bg-slate-50">
                    <td class="p-4 font-medium text-slate-900">{{ $row->title }}</td>
                    <td class="p-4 text-slate-600">
                        {{ $row->course }}<span class="text-slate-400"> — </span>{{ $row->lesson }}
                    </td>
                    <td class="p-4">
                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $row->status === 'Published' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $row->status }}
                        </span>
                    </td>
                    <td class="p-4 text-slate-600">{{ $row->question_count }}</td>
                    <td class="p-4 text-slate-600">{{ $row->attempt_count }}</td>
                    <td class="p-4 text-slate-600">{{ $row->average_score !== null ? $row->average_score.'%' : '—' }}</td>
                    <td class="p-4 text-slate-600">{{ $row->pass_rate !== null ? $row->pass_rate.'%' : '—' }}</td>
                </tr>

                @empty

                <tr>
                    <td colspan="7" class="p-10 text-center text-slate-500">No assessments yet.</td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection
